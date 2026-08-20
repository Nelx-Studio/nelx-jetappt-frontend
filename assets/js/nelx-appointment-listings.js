(function($){
    'use strict';

    function tableRows($wrap){
        var rows=[];
        $wrap.find('tbody tr').each(function(){
            var $tr=$(this), values=[];
            $tr.find('td').each(function(){ values.push($(this).text().replace(/\s+/g,' ').trim()); });
            rows.push({el:$tr, values:values});
        });
        return rows;
    }

    function escapeCsv(value){
        value=String(value==null?'':value);
        return '"'+value.replace(/"/g,'""')+'"';
    }

    function activeRows($wrap){
        var query=($wrap.find('.nelx-appointment-table-search input').val()||'').toLowerCase().trim();
        return tableRows($wrap).filter(function(row){
            return !query || row.values.join(' ').toLowerCase().indexOf(query)!==-1;
        });
    }

    function refreshListingActionStates($wrap){
        var $actions=$wrap.find('.nelx-actions-inline, .nelx-client-actions-inline');
        if(!$actions.length || typeof window.NELXJAF_refreshActionButtons !== 'function') return;
        window.NELXJAF_refreshActionButtons($actions);
    }

    function renderPagination($pager,page,pages){
        $pager.empty();
        function add(label,target,disabled,current){
            var $button=$('<button type="button"></button>').text(label).prop('disabled',disabled);
            if(current)$button.attr('aria-current','page');
            $button.on('click',function(){
                if(!disabled){
                    var $wrap=$pager.closest('.nelx-appointment-table-wrap');
                    $wrap.data('page',target);
                    renderTable($wrap);
                }
            }).appendTo($pager);
        }
        add('<<',1,page<=1,false);
        if(pages<=7){
            for(var i=1;i<=pages;i++) add(String(i),i,false,i===page);
        }else{
            var from=Math.max(1,page-2), to=Math.min(pages,page+2);
            if(from>1) add('1',1,false,page===1);
            if(from>2) $pager.append('<span class="nelx-pagination-ellipsis">…</span>');
            for(var j=from;j<=to;j++) add(String(j),j,false,j===page);
            if(to<pages-1) $pager.append('<span class="nelx-pagination-ellipsis">…</span>');
            if(to<pages) add(String(pages),pages,false,page===pages);
        }
        add('>>',pages,page>=pages,false);
    }

    function renderTable($wrap){
        var filtered=activeRows($wrap), total=filtered.length;
        var $select=$wrap.find('.nelx-appointment-table-length select'), length=parseInt($select.val(),10)||10;
        var page=parseInt($wrap.data('page'),10)||1;
        var pages=length===-1?1:Math.max(1,Math.ceil(total/length));
        if(page>pages) page=pages;
        $wrap.data('page',page);
        var start=length===-1?0:(page-1)*length;
        var end=length===-1?total:Math.min(total,start+length);
        var rows=tableRows($wrap);
        rows.forEach(function(row){row.el.hide();});
        filtered.slice(start,end).forEach(function(row){row.el.show();});
        var info=total?('Showing '+(start+1)+' to '+end+' of '+total+' entries'):'Showing 0 to 0 of 0 entries';
        $wrap.find('.nelx-appointment-table-info').text(info);
        $wrap.find('.nelx-appointment-table-pagination').each(function(){renderPagination($(this),page,pages);});
    }

    function exportRows($wrap){ return activeRows($wrap).map(function(r){return r.values;}); }
    function headers($wrap){return $wrap.find('thead th').map(function(){return $(this).text().trim();}).get();}
    function download(content,name,type){
        var blob=new Blob([content],{type:type});
        var url=URL.createObjectURL(blob), a=document.createElement('a');
        a.href=url;a.download=name;document.body.appendChild(a);a.click();a.remove();setTimeout(function(){URL.revokeObjectURL(url);},500);
    }

    function exportData($wrap,type){
        var head=headers($wrap), data=exportRows($wrap);
        if(type==='copy'){
            var text=[head].concat(data).map(function(row){return row.join('\t');}).join('\n');
            if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(text);}else{var ta=$('<textarea>').val(text).appendTo('body').select();document.execCommand('copy');ta.remove();}
            return;
        }
        if(type==='csv'){
            download([head].concat(data).map(function(row){return row.map(escapeCsv).join(',');}).join('\n'),'appointments.csv','text/csv;charset=utf-8');return;
        }
        if(type==='excel'){
            var html='<table><thead><tr>'+head.map(function(h){return '<th>'+ $('<div>').text(h).html()+'</th>';}).join('')+'</tr></thead><tbody>';
            data.forEach(function(row){html+='<tr>'+row.map(function(v){return '<td>'+ $('<div>').text(v).html()+'</td>';}).join('')+'</tr>';});
            html+='</tbody></table>';
            download('\ufeff'+html,'appointments.xls','application/vnd.ms-excel');return;
        }
        var title='Appointments';
        var printHtml='<!doctype html><html><head><title>'+title+'</title><style>body{font-family:Arial,sans-serif;padding:20px}table{width:100%;border-collapse:collapse}th,td{border:1px solid currentColor;padding:7px;text-align:left}th{font-weight:600}</style></head><body><h2>'+title+'</h2><table><thead><tr>'+head.map(function(h){return '<th>'+ $('<div>').text(h).html()+'</th>';}).join('')+'</tr></thead><tbody>';
        data.forEach(function(row){printHtml+='<tr>'+row.map(function(v){return '<td>'+ $('<div>').text(v).html()+'</td>';}).join('')+'</tr>';});
        printHtml+='</tbody></table></body></html>';
        var win=window.open('','_blank');
        if(!win)return;
        win.document.open();win.document.write(printHtml);win.document.close();
        win.focus();setTimeout(function(){win.print();},250);
    }

    function pad(n){return n<10?'0'+n:String(n);}
    function monthKey(year,month){return year+'-'+pad(month);}
    function escapeHtml(value){return $('<div>').text(value==null?'':String(value)).html();}
    function monthName(year,month){return new Date(year,month-1,1).toLocaleString(undefined,{month:'long',year:'numeric'});}

    function getInitialMonth(){
        var source=(window.NELXJAF&&NELXJAF.today)?NELXJAF.today:null;
        var date=source?new Date(source+'T00:00:00'):new Date();
        if(isNaN(date.getTime())) date=new Date();
        return {year:date.getFullYear(),month:date.getMonth()+1};
    }

    function refreshCalendarActionStates($wrap){
        var $actions=$wrap.find('.nelx-calendar-event-actions .nelx-actions-inline, .nelx-calendar-event-actions .nelx-client-actions-inline');
        if(!$actions.length || typeof window.NELXJAF_refreshActionButtons !== 'function') return;
        window.NELXJAF_refreshActionButtons($actions);
    }

    function fetchCalendarMonth($wrap,year,month){
        var key=monthKey(year,month), cache=$wrap.data('calendar-cache')||{};
        if(cache[key]){
            renderCalendar($wrap,year,month,cache[key]);
            return $.Deferred().resolve(cache[key]).promise();
        }

        var $message=$wrap.find('.nelx-calendar-message');
        $message.removeClass('is-error is-empty').text('Loading appointments…').show();
        $wrap.addClass('is-calendar-loading');

        var request=$.ajax({
            url:NELXJAF.root+'appointments/calendar',
            method:'GET',
            data:{view:$wrap.data('view'),year:year,month:month},
            beforeSend:function(xhr){xhr.setRequestHeader('X-WP-Nonce',NELXJAF.nonce);}
        });

        request.done(function(response){
            var data=response&&Array.isArray(response.appointments)?response.appointments:[];
            cache[key]=data;
            $wrap.data('calendar-cache',cache);
            renderCalendar($wrap,year,month,data);
        }).fail(function(xhr){
            console.error('Calendar appointments request failed:',xhr.responseText);
            $message.addClass('is-error').text('Unable to load appointments for this month.').show();
            $wrap.find('.nelx-appointment-calendar-grid .nelx-calendar-day').remove();
        }).always(function(){
            $wrap.removeClass('is-calendar-loading');
        });
        return request;
    }

    function renderCalendar($wrap,year,month,appointments){
        var $grid=$wrap.find('.nelx-appointment-calendar-grid');
        $grid.find('.nelx-calendar-day').remove();
        $wrap.find('.nelx-calendar-title').text(monthName(year,month));

        var byDate={};
        (appointments||[]).forEach(function(item){
            if(!byDate[item.date]) byDate[item.date]=[];
            byDate[item.date].push(item);
        });

        var first=new Date(year,month-1,1), startDay=first.getDay();
        var daysInMonth=new Date(year,month,0).getDate();
        var prevMonthDays=new Date(year,month-1,0).getDate();
        var totalCells=Math.ceil((startDay+daysInMonth)/7)*7;
        var today=(window.NELXJAF&&NELXJAF.today)?NELXJAF.today:(new Date().getFullYear()+'-'+pad(new Date().getMonth()+1)+'-'+pad(new Date().getDate()));

        for(var cell=0;cell<totalCells;cell++){
            var dayNumber=cell-startDay+1, cellDate, currentMonth=true, displayNumber;
            if(dayNumber<1){
                var pm=month-1, py=year;
                if(pm<1){pm=12;py--;} 
                displayNumber=prevMonthDays+dayNumber;
                cellDate=py+'-'+pad(pm)+'-'+pad(displayNumber);
                currentMonth=false;
            }else if(dayNumber>daysInMonth){
                var nm=month+1, ny=year;
                if(nm>12){nm=1;ny++;}
                displayNumber=dayNumber-daysInMonth;
                cellDate=ny+'-'+pad(nm)+'-'+pad(displayNumber);
                currentMonth=false;
            }else{
                displayNumber=dayNumber;
                cellDate=year+'-'+pad(month)+'-'+pad(dayNumber);
            }

            var $day=$('<div class="nelx-calendar-day" role="gridcell"></div>');
            if(!currentMonth)$day.addClass('is-other-month');
            if(cellDate===today)$day.addClass('is-today');
            $day.append('<div class="nelx-calendar-day-number">'+displayNumber+'</div>');

            var items=byDate[cellDate]||[];
            var $events=$('<div class="nelx-calendar-events"></div>');
            items.forEach(function(item){
                var $event=$('<article class="nelx-calendar-event"></article>').attr('data-appointment-id',item.id);
                $event.append('<div class="nelx-calendar-event-time">'+escapeHtml(item.time)+'</div>');
                $event.append('<div class="nelx-calendar-event-person">'+escapeHtml(item.person)+'</div>');
                $event.append('<div class="nelx-calendar-event-service">'+escapeHtml(item.service)+'</div>');
                if(item.action_html){
                    $event.append('<div class="nelx-calendar-event-actions">'+item.action_html+'</div>');
                }
                $events.append($event);
            });
            $day.append($events);
            $grid.append($day);
        }

        var $message=$wrap.find('.nelx-calendar-message');
        if(!appointments || !appointments.length){
            $message.addClass('is-empty').text('No appointments found for this month.').show();
        }else{
            $message.removeClass('is-empty is-error').hide().text('');
        }

        // Calendar events are injected dynamically, so refresh their action state
        // using the same batch endpoint used by the legacy action-button system.
        refreshCalendarActionStates($wrap);
    }

    function showDisplay($wrap,display){
        var isCalendar=display==='calendar';
        $wrap.find('.nelx-display-tab').each(function(){
            var active=$(this).data('display')===display;
            $(this).toggleClass('is-active',active).attr('aria-selected',active?'true':'false');
        });
        $wrap.find('[data-display-panel="list"]').prop('hidden',isCalendar);
        $wrap.find('[data-display-panel="calendar"]').prop('hidden',!isCalendar);
        if(isCalendar){
            var state=$wrap.data('calendar-state')||getInitialMonth();
            $wrap.data('calendar-state',state);
            fetchCalendarMonth($wrap,state.year,state.month);
        }
    }

    function initCalendar($wrap){
        if($wrap.data('calendar-initialized'))return;
        $wrap.data('calendar-initialized',true).data('calendar-cache',{}).data('calendar-state',getInitialMonth());
        $wrap.on('click','.nelx-display-tab',function(){showDisplay($wrap,$(this).data('display'));});
        $wrap.on('click','.nelx-calendar-nav',function(){
            var state=$wrap.data('calendar-state')||getInitialMonth();
            if($(this).data('calendar-nav')==='prev') state.month--; else state.month++;
            if(state.month<1){state.month=12;state.year--;}
            if(state.month>12){state.month=1;state.year++;}
            $wrap.data('calendar-state',state);
            fetchCalendarMonth($wrap,state.year,state.month);
        });
    }

    function init($wrap){
        if($wrap.data('nelx-initialized'))return;
        $wrap.data('nelx-initialized',true).data('page',1);
        initCalendar($wrap);
        $wrap.on('input','.nelx-appointment-table-search input',function(){$wrap.data('page',1);renderTable($wrap);});
        $wrap.on('change','.nelx-appointment-table-length select',function(){$wrap.data('page',1);renderTable($wrap);});
        $wrap.on('click','.nelx-appointment-table-export button',function(){exportData($wrap,$(this).data('export'));});
        renderTable($wrap);
    }

    $(function(){
        $('.nelx-appointment-table-wrap').each(function(){init($(this));});
    });
})(jQuery);

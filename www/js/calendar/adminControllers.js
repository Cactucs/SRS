

var REFRESH_INTERVAL = 10000;
var api_path = basePath + '/api/program/';

function AdminCalendarCtrl($scope, $http, $q, $timeout) {
    $scope.option = ''; // indexovane bloky - pro snadne vyhledavani a prirazovani
    $scope.event = null; // udalost se kterou prave pracuji
    $scope.config = null; // konfiguracni nastaveni pro kalendar
    $scope.blocks = []; // neindexovane bloky - v poli - pro filtrovani
    $scope.startup = function() {
        var promise, promisses = [];
        promise = $http.get(api_path+"getblocks", {})
            .success(function(data, status, headers, config) {
                $scope.options = data;
            }).error(function(data, status, headers, config) {
                $scope.status = status;
            });
        promisses.push(promise);

        promise = $http.get(api_path+"getprograms", {})
            .success(function(data, status, headers, config) {
                $scope.events = data;
            }).error(function(data, status, headers, config) {
                $scope.status = status;
            });
        promisses.push(promise);

        promise = $http.get(api_path+"getcalendarconfig", {})
            .success(function(data, status, headers, config) {
                $scope.config = data;

            }).error(function(data, status, headers, config) {
                $scope.status = status;
            });
        promisses.push(promise);

        //pote co jsou vsechny inicializacni ajax requesty splneny
        $q.all(promisses).then(function() {
            angular.forEach($scope.events, function(event, key) {
                event.block = $scope.options[event.block];
                setColor(event);

            });
            angular.forEach($scope.options, function(block, key) {
                $scope.blocks.push(block);

            });
            if (!$scope.config.is_allowed_modify_schedule) {
                $scope.warning = 'Úprava harmonogramu semináře je zakázána. Nemáte právo spravovat harmonogram nebo musíte povolit úpravu harmonogramu v modulu konfigurace.';
            }
            bindCalendar($scope);
        });
    }

    $scope.startup();


    $scope.saveEvent = function(event) {
        $scope.event = event;
        event.startJSON = fixDate(event.start);
        event.endJSON = fixDate(event.end);
        if (event.block) { //php si neumi poradit s html apod v jsondecode
            event.block.perex = '';
            event.block.description = '';
        }
        seen = [];
        var json = JSON.stringify(event, function(key, val) {
            if (typeof val == "object") {
                if ($.inArray(val, seen) >= 0)
                    return undefined;
                seen.push(val);
            }
            if (key =='source') { // tyto data nepotřebujeme
                return undefined;
            }
            return val
        });
        $http.post(api_path+"setprogram?data="+json)
        .success(function(data, status, headers, config) {
           $scope.event.id = data['id'];

        });
    }

    $scope.update = function(event, option) {
        $('#blockModal').modal('hide');
        $scope.event.mandatory = event.mandatory;
        if (option) {
        $scope.event.title = option.name;
        $scope.event.attendees_count = 0;
        if ($scope.event.block) {
            var old_block = $scope.event.block;
        }
        else {
            var old_block = null;
        }
        $scope.event.block = $scope.options[option.id];

        if ($scope.event.block != old_block) {
            if (old_block != null) old_block.program_count--;
            $scope.event.block.program_count++;
        }
        $scope.event.duration = $scope.event.block.duration;
            var end = bindEndToBlockDuration($scope.event.start, $scope.event._end, $scope.event.block.duration, $scope.config.basic_block_duration);
        $scope.event.end = end;

        }
        else {
            $scope.event.title = '(Nepřiřazeno)';
            $scope.event.block = null;
        }
        setColor($scope.event);
        $scope.saveEvent($scope.event);
        $('#calendar').fullCalendar('updateEvent', [$scope.event]);
    }

    $scope.remove = function(event) {
        if (event.block != null || event.block != undefined) {
            event.block.program_count--;
        }
        $http.post(api_path+"deleteprogram/"+event.id);
        $('#blockModal').modal('hide');
        $('#calendar').fullCalendar( 'removeEvents',[event._id] );
    }

    $scope.refreshForm = function() {
        this.event = $scope.event;
        if ($scope.event.block != undefined && $scope.event.block != null) {
            var id = $scope.event.block.id
            this.option = $scope.options[id];
        }
        else {
            this.option = null;
        }

        $scope.$apply();
    }
}

function bindCalendar(scope) {

    var local_config = {
        aspectRatio: 1.6,
        editable: scope.config.is_allowed_modify_schedule,
        droppable: scope.config.is_allowed_modify_schedule,
        events: scope.events,
        year: scope.config.year,
        month: scope.config.month,
        date: scope.config.date,
        selectable: scope.config.is_allowed_modify_schedule,
        selectHelper: scope.config.is_allowed_modify_schedule,
        seminarLength: scope.config.seminar_duration,
        firstDay: scope.config.seminar_start_day,


        select: function(start, end, allDay) {
            end = bindEndToBasicBlockDuration(start, end, scope.config.basic_block_duration);
            var title = '(Nepřiřazeno)';
            var event = {
                title: title,
                start: start,
                end: end,
                allDay: allDay,
                mandatory: false
            }
            scope.event = event;
            setColor(scope.event);
            scope.saveEvent(event);
            calendar.fullCalendar('renderEvent',
                scope.event,
                true // make the event "stick"
            );
            calendar.fullCalendar('unselect');
        },

        eventClick: function(event, element) {
            if (scope.config.is_allowed_modify_schedule) {
                scope.event = event;
                scope.refreshForm();
                $('#blockModal').modal('show');
            }

        },

        eventDrop: function( event, jsEvent, ui, view ) {
            scope.event = event;
            scope.saveEvent(event);
        },

        eventResize: function( event, dayDelta, minuteDelta, revertFunc, jsEvent, ui, view  ) {
            if (event.block == null || event.block == undefined) {
                var end = bindEndToBasicBlockDuration(event.start, event.end, scope.config.basic_block_duration);
                event.end = end;
                scope.event = event;
                scope.saveEvent(scope.event);
                $('#calendar').fullCalendar('updateEvent', event);
            }
            else {
                flashMessage('Položkám s přiřazeným programovým blokem nelze měnit délku', 'error');
                revertFunc();
            }
        },

        drop: function(date, allDay) {

            // retrieve the dropped element's stored Event Object
            var originalEventObject = $(this).data('eventObject');

            // we need to copy it, so that multiple events don't have a reference to the same object
            var event = $.extend({}, originalEventObject);

            // assign it the date that was reported
            event.start = date;
            event.attendees_count = 0;
            event.allDay = allDay;
            event.block.program_count++;
            event.end = bindEndToBlockDuration(date, null, event.block.duration, scope.config.basic_block_duration);
            scope.event = event;
            setColor(scope.event);
            scope.saveEvent(event);

            // render the event on the calendar
            // the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
            $('#calendar').fullCalendar('renderEvent', event, true);
        },

        eventRender: function(event, element) {
            var options = {}
            options.html = true;
            options.trigger = 'hover';
            options.title = event.title;
            options.content = '';
            if (event.block != null && event.block != undefined) {
                options.content += "<ul class='no-margin block-properties'>";
                options.content += "<li><span>lektor:</span> "+ event.block.lector +"</li>";
                options.content += "<li><span>Kapacita:</span>"+event.attendees_count+"/"+ event.block.capacity +"</li>";
                options.content += "<li><span>Lokalita:</span> "+ event.block.location +"</li>";
                options.content += "<li><span>Pomůcky:</span> "+ event.block.tools +"</li>";
                options.content +="</ul>";
                options.content +="<p>"+event.block.perex+"</p>";
            }

            element.find('.fc-event-title').popover(options);
        }
    }

    var calendar = $('#calendar').fullCalendar(jQuery.extend(local_config, localization_config));
}





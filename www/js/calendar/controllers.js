function CalendarCtrl($scope, $http) {
    $scope.option = '';
    $scope.event = null;
    $scope.config = null;
    $scope.counter = 0

    $http.post("./get", {})
        .success(function(data, status, headers, config) {
            $scope.events = data;
            angular.forEach($scope.events, function(event, key) {
                setColor(event);
            });
            $http.post("./getcalendarconfig", {})
                .success(function(data, status, headers, config) {
                    $scope.config = data;
                    bindCalendar($scope);
                }).error(function(data, status, headers, config) {
                    $scope.status = status;
                });
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });

    $http.post("./getoptions", {})
        .success(function(data, status, headers, config) {
            $scope.options = data;
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });





    $scope.saveEvent = function(event) {
        $scope.event = event;
        event.startJSON = fixDate(event.start);
        event.endJSON = fixDate(event.end);
        seen = [];
        var json = JSON.stringify(event, function(key, val) {
            if (typeof val == "object") {
                if (seen.indexOf(val) >= 0)
                    return undefined
                seen.push(val)
            }
            if (key =='source') { // tyto data nepotřebujeme
                return undefined;
            }
            return val
        });
        $http.post("./set?data="+json)
        .success(function(data, status, headers, config) {
           $scope.event.id = data['id'];

        });
    }

    $scope.update = function(event, option) {
        $('#blockModal').modal('hide');
        $scope.event.mandatory = event.mandatory;
        if (option) {
        $scope.event.title = option.name;
        $scope.event.block = $scope.options[option.id];
       //console.log($scope.event.block);
        var end = bindEndToBlockDuration($scope.event.start, $scope.event._end, $scope.event.block.duration, $scope.config.basic_block_duration);
        //console.log($scope.event);
        console.log(end);
        $scope.event.end = end;
        //$scope.event._end = end;
        console.log($scope.event);
        }
        else {
            $scope.event.title = '(Nepřiřazeno)';
            $scope.event.block = null;
        }
        setColor($scope.event);
        $scope.saveEvent($scope.event);
        $('#calendar').fullCalendar('updateEvent', [$scope.event]);
    };

    $scope.delete = function(event) {
        $http.post("./delete/"+event.id);
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
    var calendar = $('#calendar').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        selectable: true,
        selectHelper: true,
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
            //console.log(event);
            scope.event = event;
            scope.refreshForm();
            $('#blockModal').modal('show');

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

        eventResizeStart: function( event, jsEvent, ui, view ) {
            return false;
        },

        eventRender: function(event, element) {
            //element.qtip({'content': bindTooltipContent(event)});
        },

        editable: true,
        events: scope.events,
        firstDay: 1,
        year: scope.config.year,
        month: scope.config.month,
        date: scope.config.date,
        defaultView: 'agendaWeek',
        ignoreTimezone: true,
        slotMinutes: 15
    });
}





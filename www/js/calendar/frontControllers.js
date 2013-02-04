
function FrontCalendarCtrl($scope, $http, $q) {
    $scope.option = ''; // indexovane bloky - pro snadne vyhledavani a prirazovani
    $scope.event = null; // udalost se kterou prave pracuji
    $scope.config = null; // konfiguracni nastaveni pro kalendar

    var promise, promisses = [];
    var api_path = basePath + '/admin/program/';

    promise = $http.post(api_path+"getoptions", {})
        .success(function(data, status, headers, config) {
            $scope.options = data;
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });
    promisses.push(promise);

    promise = $http.post(api_path+"./get?userAttending=1", {})
        .success(function(data, status, headers, config) {
            $scope.events = data;
        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });
    promisses.push(promise);

    promise = $http.post(api_path+"./getcalendarconfig", {})
        .success(function(data, status, headers, config) {
            $scope.config = data;

        }).error(function(data, status, headers, config) {
            $scope.status = status;
        });
    promisses.push(promise);

    //pote co jsou vsechny inicializacni ajax requesty splneny
    $q.all(promisses).then(function() {
        angular.forEach($scope.events, function(event, key) {
            setColor(event);
            event.block = $scope.options[event.block];

        });
        console.log($scope.events);
        bindCalendar($scope);
    });

    $scope.attend = function(event) {
        $http.post(api_path+"attend/"+event.id)
            .success(function(data, status, headers, config) {
                flashMessage(data['message'], data['status']);
                if (data['status'] == 'success') {
                    event.attends = true;
                    event.color = 'green';
                }
                $('#calendar').fullCalendar('updateEvent', event);
            }).error(function(data, status, headers, config) {
                $scope.status = status;
         });
    }

    $scope.unattend = function(event) {
        $http.post(api_path+"unattend/"+event.id)
            .success(function(data, status, headers, config) {
                flashMessage(data['message'], data['status']);
                if (data['status'] == 'success') {
                    event.attends = false;
                    event.color = null;
                }
                $('#calendar').fullCalendar('updateEvent', event);
            }).error(function(data, status, headers, config) {
                $scope.status = status;
            });
    }
}

function bindCalendar(scope) {

    var local_config = {
        editable: false,
        droppable: false,
        events: scope.events,
        year: scope.config.year,
        month: scope.config.month,
        date: scope.config.date,
        selectable: false,
        selectHelper: false,

        eventClick: function(event, element) {
            scope.event = event;
            if (event.attends == false) {
                scope.attend(event);
            }
            else {
                scope.unattend(event);
            }
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
                options.content += "<li><span>Kapacita:</span> "+ event.block.capacity +"</li>";
                options.content += "<li><span>Lokalita:</span> "+ event.block.location +"</li>";
                options.content += "<li><span>Pomůcky:</span> "+ event.block.tools +"</li>";
                options.content +="</ul>";
            }

            element.find('.fc-event-title').popover(options);
        }
    }

    var calendar = $('#calendar').fullCalendar(jQuery.extend(local_config, localization_config));
}





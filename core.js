// public/core.js
var evergreenDoc = angular.module('evergreenDoc', []);


function mainController($scope, $http, $timeout) {
	$scope.formData = {};

	

	// when landing on the page, get all todos and show them

	

    (function tick() {
    	var initialList= [];

	var groupings={}; 
	$scope.selectedAlerts={};
       $http({method: 'GET', url: '/statuses'})
		.success(function(data){
			$scope.statuses = data.slice().reverse();
			var count = 0;
			for (var i = 0; i < data.length; i++) {
				if (groupings[data[i].patient_id] === undefined){
					count++;
					groupings[data[i].patient_id]=[];
					groupings[data[i].patient_id]['beacon'] =[];
					groupings[data[i].patient_id]['beacon'].push(data[i]);

				} else {
					groupings[data[i].patient_id]['beacon'].push(data[i]);
				}
				
				

			};

			$scope.count = count;
			

			$scope.groupingList = groupings;
			console.log(groupings);
			// $timeout(tick, 1000);
			// 
			// 
			$http({method: 'GET', url: '/sessions'})
				.success(function(data){
					// $timeout(tick, 1000);
					for (var i = 0; i < data.length; i++) {
						var currentTime = new Date();
						var timeDiff = Math.abs(data[i].emergency- currentTime.getTime()/1000);
						if(timeDiff < 10){
							groupings[data[i].patient]['emergency'] = true;
						}
					};
					
				});       
			
		});

    })();

}


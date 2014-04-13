// public/core.js
var evergreenDoc = angular.module('evergreenDoc', []);


function mainController($scope, $http, $timeout) {
	$scope.formData = {};

	var initialList= [];

	var groupings={}; 

	// when landing on the page, get all todos and show them

	$scope.openDetails = function(patient_id){
		console.log('someting');
		$scope.groupingList = groupings[patient_id];
	};

    (function tick() {
       $http({method: 'GET', url: '/statuses'})
		.success(function(data){
			$scope.statuses = data.slice().reverse();
			
			for (var i = 0; i < data.length; i++) {
				if (groupings[data[i].patient_id] === undefined){
					groupings[data[i].patient_id] =[];
					initialList.push(data[i])

				} else {
					groupings[data[i].patient_id].push(data[i]);
				}
				
				

			};
			$scope.initialList = initialList;
			console.log(groupings);
			// $timeout(tick, 1000);
			
		});
            
        
    })();


}


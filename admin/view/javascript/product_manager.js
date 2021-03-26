var app = angular.module('app', []);

app.config(function ($interpolateProvider) {
    $interpolateProvider.startSymbol('{[{').endSymbol('}]}');
});

app.controller('productController', ['$scope', '$http', function ($scope, $http) {

    $scope.products = [];

    $scope.filter = {
        filter_name: '',
        filter_price: '',
        filter_quantity: '',
        filter_status: '',
        filter_model: ''
    }

    $scope.applyFilter = function () {
        let url = 'index.php?route=catalog/product_manager/getProducts&user_token=VveW7E13O65dUIaXLM06Y2qJjCcrlRLw';

        url = url + `&filter_name=${$scope.filter.filter_name}&filter_price=${$scope.filter.filter_price}&filter_quantity=${$scope.filter.filter_quantity}&filter_status=${$scope.filter.filter_status}&filter_model=${$scope.filter.filter_model}`

        $http.get(url)
            .then(function (response) {
                showSuccess("Ürünler çekildi.")
                $scope.products = response.data;
            }, function (err) {
                showError(err);
            });
    }
}]);


function showSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Başarılı.',
        text: message
    })
}

function showError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Hata...',
        text: message
    })
}
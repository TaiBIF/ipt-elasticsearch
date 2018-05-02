var app = angular.module('emlQueryApp', []);
app.controller('emlQueryController', ['$scope', 'postService', '$sce', function ($scope, postService, $sce) {

  // $scope.taxon_index_top = {'height':'800px', 'overflow':'scroll'};

  $scope.q = '"國家公園"';

  function _prepareQuery () {
    $scope.eml_total = 0;
    $scope.num_of_occurrence = 0;
    $scope.eml_list = [];
  }

  _prepareQuery();

  $scope.query_endpoint = "http://test.taibon.tw/indexIPT/es_query.php";
  $scope.getEml = function () {
    _prepareQuery();
    postService.post($scope.query_endpoint, {q: $scope.q})
      .then(function (res) {
        $scope.eml_total = res.hits.total;
        var emls = res.hits.hits;
        emls.forEach(function (eml /*_source.eml_eml*/) {
          var eml_item = {};
          eml_item.title = '#N/A';
          try {
            eml_item.title = eml._source.eml_eml[0].dataset[0].title[0]._value;
          } catch (err) {}
          eml_item.num_of_occurrence = eml._source.num_of_occurrence;
          eml_item.link = eml._source.eml_link;
          eml_item.id = eml._source.eml_link.split('=').pop();
          $scope.num_of_occurrence += eml_item.num_of_occurrence;

          var contacts;
          var eml_contacts = [];
          var eml_contact_emails = [];
          var eml_contact_positions = [];
          var givenName = '#N/A';
          var surName = '#N/A';
          var email = '#N/A';
          var positionName = '#N/A';
          try {
            var _contacts = [];
            var _emails = [];
            var _positions = [];
            contacts = eml._source.eml_eml[0].dataset[0].contact;
            contacts.forEach(function(contact) {
              givenName = '#N/A';
              surName = '#N/A';
              positionName = '#N/A';
              email = '#N/A';

              try {
                positionName = contact.positionName[0]._value;
              } catch (err) {}

              try {
                givenName = contact.individualName[0].givenName[0]._value;
              } catch (err) {}

              try {
                surName = contact.individualName[0].surName[0]._value;
              } catch (err) {}

              try {
                email = contact.electronicMailAddress[0]._value;
              } catch (err) {}

              var _contact = (email !== '#N/A')?('<a href="mailto:' + email + '" target="_blank">' + givenName + ' ' + surName + '</a>'):(givenName + ' ' + surName);
              _contacts.push(_contact);
              _emails.push(email);
              _positions.push(positionName);

            });

            eml_contacts.push(_contacts.join(', '));
            eml_contact_emails.push(_emails.join(', '));
            eml_contact_positions.push(_positions.join(', '));

          } catch (err) {}

          var project_funding = '#N/A';
          try {
            project_funding = eml._source.eml_eml[0].dataset[0].project[0].funding[0].para[0]._value;
          }
          catch (err) {}

          eml_item.contacts =  eml_contacts.join(', ');
          eml_item.contact_emails = eml_contact_emails.join(', ');
          eml_item.contact_positions = eml_contact_positions.join(', ');
          eml_item.project_funding = project_funding;
          $scope.eml_list.push(eml_item);
        });
      });
  }

  $scope.enterToSubmit = function (e) {
    if (e.key == 'Enter') {
      $scope.getEml();
    }
  }

  $scope.htmlMe = function (s) {
    return $sce.trustAsHtml(s);
  }

  $scope.getEml();

}]);


app.factory('postService', ['$http', function($http) {
  var post = function (url, reqData) {
    return $http.post(url, reqData)
      .then (
        function (response) {
          return response.data;
        },
        function (httpError) {
          // translate the error
          throw httpError.status + " : " +
                httpError.data;
        }
      );
  }
  return {post: post}
}]);




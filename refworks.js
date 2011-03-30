// js file for refworks export -- requires jQuery

var scriptUrl = 'http://library.example.edu/refworksexport.php';
var recordId = $('#recordnum').attr('href').match(/record=b(\d+)/)[1];
$('#bibDisplayBody div:first').append('<a href="'+scriptUrl+'?bibnum='+recordId+'" target="refworks">Export to RefWorks</a>');

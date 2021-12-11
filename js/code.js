/* Javascript code to process the front end request and 
process the response from the backend. */

$(document).ready(function(){
	
	//On change or upload of a new input file
	$(document).on('change','#file_csv',function() {
		//Retrieve the name of the file
		var file = $('#file_csv')[0].files[0].name;
		//Update the name of the uploaded file in the html content
		$('#file_label').text(file);
	});
	
	//On click of submit button
	$("#csv_uploader").on("submit",function(e){
		//To not submit the form which is the default action
		e.preventDefault();
		/* Ajax request to communicate with the backend and retrieve
		and display the response in the frontend. */
		$.ajax({
			url:"php/backend.php",
			data:new FormData(this),
			type:"POST",
			contentType:false,
			cache:false,
			processData:false,
			success:function(res){
				//Display the table in the frontend
				$("#response").html(res);
			}
		});
	});
});
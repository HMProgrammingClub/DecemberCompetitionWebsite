function getOrderedEntries() {
	var result = $.ajax({
		url: "Backend/submission", 
		async: false,
		method: "GET",
		data: {getAll: 1}
	})
	return result.responseJSON;
}

function submitEntry(formID, callback, errorCallBack) {
	var formData = new FormData($("#"+formID)[0]);
	console.log(formData)
	$.ajax({
		url: "Backend/submission", 
		async: false,
		method: "POST",
		data: formData,
		processData: false,
		contentType: false,
		xhr: function() {
			var myXhr = $.ajaxSettings.xhr();
			return myXhr;
		},
		success: function(result) {
			console.log(result)
			callback(result)
		},
		error: function (xhr, ajaxOptions, thrownError) {
			console.log(xhr.responseText);
			console.log(thrownError);
			errorCallBack()
		}
	})
}

function displayAlerts(errors) {
	$("#errorbox").empty()
	for(var i=0; i<errors.length; i++) {
		$("#errorbox").append($("<div class='alert alert-danger' role='alert'><span class='glyphicon glyphicon-exclamation-sign' aria-hidden='true'></span><span class='sr-only'>Error:</span> "+errors[i]+"</div>"))
	} 
}

function populateLeaderboard() {
	$("#leaderboard").empty()
	var entries = getOrderedEntries()
	for(var a = 0; a < entries.length; a++) {
		var entry = entries[a]
		$("#leaderboard").append($("<tr><th scope='row'>"+(a+1)+"</th><td>"+entry.name+"</td><td>"+entry.distance+"</td></tr>"))
	}
}

$(document).ready(function() {
	populateLeaderboard()
	$("#submitFile").click(function() {
		var errors = [];
		var nameLength = $('#nameField').val().length
		if (nameLength > 64) {
			errors.push("Name is too long; must be less than 65 characters.")
		} if (nameLength < 5) {
			errors.push((nameLength == 0)?"Must input a name.":"Name must be greater than 5 characters.")
		} if ($("#submissionFile").val() == '') {
			errors.push("No file selected.")
		}

		if (errors.length <= 0)  {
			submitEntry("submissionForm", function(result) {
				$("#succbox").empty().append($("<div class='alert alert-success' role='alert'>Your score was <strong>"+parseInt(result)+"</strong>.</div>"))
				populateLeaderboard()
			}, function() {
				errors.push("There was a problem with your file. Was it in the right format?")
			})
		} 

		displayAlerts(errors)
	})
})
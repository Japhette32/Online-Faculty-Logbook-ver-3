function openModal() {
    var modal = document.getElementById('editScheduleModal');
    modal.style.display = 'block';
    setTimeout(function() {
        modal.classList.add('show');
    }, 10); // Slight delay to trigger the transition
}

function closeModal() {
    var modal = document.getElementById('editScheduleModal');
    modal.classList.remove('show');
    setTimeout(function() {
        modal.style.display = 'none';
    }, 300); // Wait for the transition to complete
}

function addTime(day) {
    var container = document.getElementById(day.toLowerCase() + 'Inputs');
    var newInput = document.createElement('div');
    newInput.className = 'time-inputs';
    newInput.innerHTML = `
        <input type="time" name="${day.toLowerCase()}StartTime[]">
        <span>-</span>
        <input type="time" name="${day.toLowerCase()}EndTime[]">
        <button type="button" class="btn-remove" onclick="removeTime(this, '${day.toLowerCase()}', '', '')">-</button>
    `;
    container.appendChild(newInput);
}

function removeTime(button, day, startTime, endTime) {
    var container = button.parentElement;
    container.remove();

    if (startTime && endTime) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = 'teacher.php';

        var actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete';
        form.appendChild(actionInput);

        var dayInput = document.createElement('input');
        dayInput.type = 'hidden';
        dayInput.name = 'day';
        dayInput.value = day;
        form.appendChild(dayInput);

        var startTimeInput = document.createElement('input');
        startTimeInput.type = 'hidden';
        startTimeInput.name = 'startTime';
        startTimeInput.value = startTime;
        form.appendChild(startTimeInput);

        var endTimeInput = document.createElement('input');
        endTimeInput.type = 'hidden';
        endTimeInput.name = 'endTime';
        endTimeInput.value = endTime;
        form.appendChild(endTimeInput);

        document.body.appendChild(form);
        form.submit();
    }
}

function removeRow(button, timeInterval) {
    var row = button.closest('tr');
    var day = button.closest('td').dataset.day;
    var times = timeInterval.split(' - ');
    var startTime = times[0];
    var endTime = times[1];

    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'teacher.php';

    var actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'delete';
    form.appendChild(actionInput);

    var dayInput = document.createElement('input');
    dayInput.type = 'hidden';
    dayInput.name = 'day';
    dayInput.value = day;
    form.appendChild(dayInput);

    var startTimeInput = document.createElement('input');
    startTimeInput.type = 'hidden';
    startTimeInput.name = 'startTime';
    startTimeInput.value = startTime;
    form.appendChild(startTimeInput);

    var endTimeInput = document.createElement('input');
    endTimeInput.type = 'hidden';
    endTimeInput.name = 'endTime';
    endTimeInput.value = endTime;
    form.appendChild(endTimeInput);

    document.body.appendChild(form);
    form.submit();
}

function popup(element, student, date, time, reason, section, registrationId, status, rejectReason = '') {
    // Create a new popup element
    var popup = document.createElement("div");
    popup.classList.add("popup");

    // Create the close button
    var closeButton = document.createElement("span");
    closeButton.classList.add("close-btn");
    closeButton.innerHTML = "&times;";
    closeButton.onclick = function() {
        closePopup(popup);
    };

    // Create the content
    var popupContent = document.createElement("p");
    var statusClass = status === "Approved" ? "status-approved" : (status === "Rejected" || status === "Cancelled") ? "status-rejected" : "";
    var rejectReasonText = status === "Rejected" ? "<br>Reject Reason: <span class='reason-rejected'>" + rejectReason + "</span>" : "";
    popupContent.innerHTML = "Section: " + section + "<br>Student: " + student + "<br>Date: " + date + "<br>Time: " + time + "<br>Reason: " + reason + "<br>Status: <span class='" + statusClass + "'>" + status + "</span>" + rejectReasonText;

    // Append the close button and content to the popup
    popup.appendChild(closeButton);
    popup.appendChild(popupContent);

    // Create a button container
    var buttonContainer = document.createElement("div");
    buttonContainer.classList.add("button-container");

    // Create the accept and reject buttons if the status is "Pending"
    if (status === "Pending") {
        var acceptButton = document.createElement("button");
        acceptButton.classList.add("accept-btn");
        acceptButton.innerHTML = "Accept";
        acceptButton.onclick = function() {
            updateStatus(registrationId, 'Approved');
            closePopup(popup);
        };
        buttonContainer.appendChild(acceptButton);

        var rejectButton = document.createElement("button");
        rejectButton.classList.add("reject-btn");
        rejectButton.innerHTML = "Reject";
        rejectButton.onclick = function() {
            openRejectReasonModal(registrationId);
            closePopup(popup);
        };
        buttonContainer.appendChild(rejectButton);
    }

    // Create the done button if the status is "Approved" or "Rejected"
    if (status === "Approved" || status === "Rejected") {
        var doneButton = document.createElement("button");
        doneButton.classList.add("done-btn");
        doneButton.innerHTML = "Done";
        doneButton.onclick = function() {
            updateStatus(registrationId, 'Done');
            closePopup(popup);
        };
        buttonContainer.appendChild(doneButton);
    }

    // Append the button container to the popup
    popup.appendChild(buttonContainer);

    // Set the position of the popup
    var rect = element.getBoundingClientRect();
    popup.style.top = rect.top + window.scrollY + "px";
    popup.style.left = rect.left + window.scrollX + "px";

    // Add the popup to the body
    document.body.appendChild(popup);

    // Display the popup with a slight delay to trigger the transition
    setTimeout(function() {
        popup.classList.add("show");
    }, 10);
}

function openRejectReasonModal(registrationId) {
    document.getElementById('rejectRegistrationId').value = registrationId;
    var modal = document.getElementById('rejectReasonModal');
    modal.style.display = 'block';
    setTimeout(function() {
        modal.classList.add('show');
    }, 10); // Slight delay to trigger the transition
}

function closeRejectReasonModal() {
    var modal = document.getElementById('rejectReasonModal');
    modal.classList.remove('show');
    setTimeout(function() {
        modal.style.display = 'none';
    }, 300); // Wait for the transition to complete
}

function closePopup(popup) {
    popup.classList.remove("show");
    setTimeout(function() {
        popup.remove();
    }, 300); // Wait for the transition to complete
}

function setRegistrationId(registrationId) {
    document.getElementById('rejectRegistrationId').value = registrationId;
}

function submitRejectReason(event) {
    event.preventDefault();
    var registrationId = document.getElementById('rejectRegistrationId').value;
    var rejectReason = document.getElementById('rejectReason').value;
    updateStatus(registrationId, 'Rejected', rejectReason);
    closeRejectReasonModal();
}

function updateStatus(registrationId, status, rejectReason = '') {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "update_status.php?status=" + status, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            location.reload();
        }
    };

    var params = "registration_id=" + encodeURIComponent(registrationId);
    if (rejectReason) {
        params += "&rejectReason=" + encodeURIComponent(rejectReason);
    }

    xhr.send(params);
}
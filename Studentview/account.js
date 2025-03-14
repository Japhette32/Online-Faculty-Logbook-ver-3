let notifications = [];

function showPopup(element, teacher, date, time, reason, status, cancelReason) {
    var popup = document.createElement("div");
    popup.classList.add("popup");

    var closeButton = document.createElement("span");
    closeButton.classList.add("close");
    closeButton.innerHTML = "&times;";
    closeButton.onclick = function() {
        closePopup(popup);
    };

    var popupContent = document.createElement("p");
    var statusClass = status === "Approved" ? "status-approved" : (status === "Rejected" || status === "Cancelled") ? "status-rejected" : "";
    var cancelReasonText = status === "Cancelled" ? "<br>Cancel Reason: <span class='reason-cancelled'>" + cancelReason + "</span>" : "";
    popupContent.innerHTML = "Teacher: " + teacher + "<br>Date: " + date + "<br>Time: " + time + "<br>Reason: " + reason + "<br>Status: <span class='" + statusClass + "'>" + status + "</span>" + cancelReasonText;

    popup.appendChild(closeButton);
    popup.appendChild(popupContent);

    if (status !== "Cancelled" && status !== "Rejected") {
        var cancelButton = document.createElement("button");
        cancelButton.classList.add("cancel-button");
        cancelButton.innerHTML = "Cancel";
        cancelButton.onclick = function() {
            openCancelReasonModal(element.dataset.registrationId);
            closePopup(popup);
        };
        popup.appendChild(cancelButton);
    }

    var rect = element.getBoundingClientRect();
    popup.style.top = rect.top + window.scrollY + "px";
    popup.style.left = rect.left + window.scrollX + "px";

    document.body.appendChild(popup);

    setTimeout(function() {
        popup.classList.add("show");
    }, 10);
}

function closePopup(popup) {
    popup.classList.remove("show");
    setTimeout(function() {
        popup.remove();
    }, 300);
}

window.onclick = function(event) {
    var popups = document.getElementsByClassName("popup");
    for (var i = 0; i < popups.length; i++) {
        if (event.target == popups[i]) {
            closePopup(popups[i]);
        }
    }
}

function openCancelReasonModal(registrationId) {
    document.getElementById('cancelRegistrationId').value = registrationId;
    var modal = document.getElementById('cancelReasonModal');
    modal.style.display = 'block';
    setTimeout(function() {
        modal.classList.add('show');
    }, 10);
}

function closeCancelReasonModal() {
    var modal = document.getElementById('cancelReasonModal');
    modal.classList.remove('show');
    setTimeout(function() {
        modal.style.display = 'none';
    }, 300);
}

function submitCancelReason(event) {
    event.preventDefault();
    var registrationId = document.getElementById('cancelRegistrationId').value;
    var cancelReason = document.getElementById('cancelReason').value;
    updateStatus(registrationId, 'Cancelled', cancelReason);
    closeCancelReasonModal();
}

function updateStatus(registrationId, status, cancelReason = '') {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "update_status.php?status=" + status, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
                var element = document.querySelector('[data-registration-id="' + registrationId + '"]');
                if (element) {
                    element.remove();
                }
                showNotification(response.message);
            } else {
                showNotification(response.message, true);
            }
        }
    };

    var params = "registration_id=" + encodeURIComponent(registrationId);
    if (cancelReason) {
        params += "&cancelReason=" + encodeURIComponent(cancelReason);
    }

    xhr.send(params);
}

function showNotification(message, isError = false) {
    var notification = document.getElementById('notification');
    var notificationMessage = document.getElementById('notificationMessage');
    notificationMessage.innerHTML = message;
    notification.style.backgroundColor = isError ? 'rgba(244, 67, 54, 0.8)' : 'rgba(76, 175, 80, 0.8)'; // Red for error, green for success
    notification.classList.add('show');
}

function closeNotification() {
    var notification = document.getElementById('notification');
    notification.classList.remove('show');
}

document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger-menu');
    const navMenu = document.querySelector('.nav ul');

    hamburger.addEventListener('click', function() {
        hamburger.classList.toggle('active');
        navMenu.classList.toggle('active');
    });

    document.querySelectorAll('.nav ul li a').forEach(link => {
        link.addEventListener('click', function() {
            hamburger.classList.remove('active');
            navMenu.classList.remove('active');
        });
    });

    document.body.classList.add('show');

    document.querySelectorAll('.nav ul li a, .logout-btn').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const href = this.getAttribute('href');
            document.body.classList.remove('show');
            setTimeout(() => {
                window.location.href = href;
            }, 600);
        });
    });

    // Show notification if there are new updates
    if (hasNewUpdates) {
        showNotification('Your schedule has been updated.');
    }
});
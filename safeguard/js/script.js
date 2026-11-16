function confirmDelete() {
    return confirm(
        "Are you sure you want to delete this emergency contact?"
    );
}

// Confirm before submitting an SOS alert
function confirmSOS() {
    return confirm(
        "Are you sure you want to submit this emergency alert?"
    );
}

// Display current date and time
function displayDateTime() {
    const element = document.getElementById("currentDateTime");

    if (element) {
        element.textContent = new Date().toLocaleString();
    }
}

document.addEventListener("DOMContentLoaded", function () {
    displayDateTime();
});

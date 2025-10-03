// Make sure you have a logout button/link with id="logoutBtn"
const logoutBtn = document.getElementById("logoutBtn");
const logoutModal = document.getElementById("logoutModal");
const cancelLogout = document.getElementById("cancelLogout");

if (logoutBtn) { // Check if the button exists on the current page
    logoutBtn.onclick = function (event) {
    event.preventDefault(); // Prevent default link behavior if it's an <a> tag
    logoutModal.style.display = "block";
  };
}

if (cancelLogout) { // Check if the cancel button exists
    cancelLogout.onclick = function (event) {
    event.preventDefault();
    logoutModal.style.display = "none";
  };
}

// Close modal if clicking outside of it
window.onclick = function (event) {
  if (event.target === logoutModal) {
    logoutModal.style.display = "none";
  }
};



    function openLogoutModal() {
        document.getElementById("logoutModal").style.display = "flex";
    }

    function closeModal() {
        document.getElementById("logoutModal").style.display = "none";
    }

    function logout() {
        window.location.href = "sign_in.php";
    }

    // --- Settings Dropdown Logic ---
    const settingsButton = document.getElementById('settings-button');
    const settingsMenu = document.getElementById('settings-menu');

    function closeSettingsDropdown() {
      if (settingsMenu && settingsButton && settingsMenu.classList.contains('show')) {
         settingsMenu.classList.remove('show');
         settingsButton.setAttribute('aria-expanded', 'false');
      }
    }

    if (settingsButton && settingsMenu) {
      settingsButton.addEventListener('click', function(event) {
        event.stopPropagation();
        const isExpanded = settingsMenu.classList.toggle('show');
        settingsButton.setAttribute('aria-expanded', isExpanded);
        // Close notification dropdown if open
        closeNotifDropdown();
      });
    }

    // --- Notification Dropdown Logic ---
    const notifButton = document.getElementById('notif-button');
    const notifMenu = document.getElementById('notif-menu'); // Get the notification menu div

    function closeNotifDropdown() { // Function to close notification dropdown
      if (notifMenu && notifButton && notifMenu.classList.contains('show')) {
         notifMenu.classList.remove('show');
         notifButton.setAttribute('aria-expanded', 'false');
      }
    }

    if (notifButton && notifMenu) { // Check if elements exist
      notifButton.addEventListener('click', function(event) {
        event.stopPropagation(); // Prevent click from immediately closing
        const isExpanded = notifMenu.classList.toggle('show'); // Toggle the 'show' class
        notifButton.setAttribute('aria-expanded', isExpanded);
        // Close settings dropdown if open
        closeSettingsDropdown();
      });
    }

    // --- Notification Filtering Logic (Copied from dashboard_notification.php and adapted) ---
    function filterNotifications(type, event) {
        // Prevent the click on filter buttons from closing the dropdown immediately
        if (event) {
            event.stopPropagation();
        }

        // Target the table body *inside the dropdown* specifically
        const notificationTableBody = document.querySelector("#notif-menu #notificationTableDropdown");
        if (!notificationTableBody) return; // Exit if table not found

        const rows = notificationTableBody.querySelectorAll("tr");
        rows.forEach(row => row.style.display = "table-row"); // Show all rows first

        // Apply filter based on type
        if (type === "system") {
            rows.forEach(row => {
                if (!row.textContent.includes("Truck")) row.style.display = "none";
            });
        } else if (type === "garbage") {
            rows.forEach(row => {
                if (!row.textContent.includes("Collection") && !row.textContent.includes("SMS Alert")) {
                    row.style.display = "none";
                }
            });
        }
        // Add more filters or "all" logic if needed (e.g., filter only unread for 'all')
        // else if (type === 'all') {
        //   rows.forEach(row => {
        //     if (!row.classList.contains('new')) {
        //         row.style.display = "none";
        //     }
        //   });
        // }

        // Update active state for tab buttons *inside the dropdown*
        const tabButtons = document.querySelectorAll("#notif-menu .tabs button");
        tabButtons.forEach(btn => btn.classList.remove("active"));

        // Find the clicked button to set it as active
        // This relies on the event target if event is passed
        if(event && event.target && event.target.tagName === 'BUTTON') {
           event.target.classList.add("active");
        } else {
           // Fallback: Find button based on onclick attribute content (less robust)
           tabButtons.forEach(btn => {
              if (btn.getAttribute('onclick').includes(`'${type}'`)) {
                  btn.classList.add('active');
              }
           });
        }
    }


    // --- Close Dropdowns on Outside Click ---
    window.addEventListener('click', function(event) {
        // Close Settings Dropdown
        if (settingsMenu && settingsButton && !settingsButton.contains(event.target) && !settingsMenu.contains(event.target)) {
            closeSettingsDropdown();
        }
         // Close Notification Dropdown
        if (notifMenu && notifButton && !notifButton.contains(event.target) && !notifMenu.contains(event.target)) {
           closeNotifDropdown();
        }
    });

    // --- Close Dropdowns on Escape Key ---
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSettingsDropdown(); // Close settings if open
            closeNotifDropdown();    // Close notifications if open
        }
    });

    // Optional: Initialize the filter on page load if needed (e.g., show 'All Unread')
    // filterNotifications('all'); // No event object needed here
    // Close modal if clicked outside the content area
    $(window).on('click', function(event) {
      $('.modal').each(function() {
          if (event.target == this) {
             // Don't close if save button is disabled (mid-request)
             if (!$(this).find('.save-btn').prop('disabled')) {
                 closeModal($(this).attr('id'));
             }
          }
      });
 });

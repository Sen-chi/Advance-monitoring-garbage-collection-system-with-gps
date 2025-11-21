const logoutBtn = document.getElementById("logoutBtn");
const logoutModal = document.getElementById("logoutModal");
const cancelLogout = document.getElementById("cancelLogout");

if (logoutBtn) { 
    logoutBtn.onclick = function (event) {
    event.preventDefault(); 
    logoutModal.style.display = "block";
  };
}

if (cancelLogout) { 
    cancelLogout.onclick = function (event) {
    event.preventDefault();
    logoutModal.style.display = "none";
  };
}

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
        closeNotifDropdown();
      });
    }

    // --- Notification Dropdown Logic ---
    const notifButton = document.getElementById('notif-button');
    const notifMenu = document.getElementById('notif-menu'); 

    function closeNotifDropdown() { 
      if (notifMenu && notifButton && notifMenu.classList.contains('show')) {
         notifMenu.classList.remove('show');
         notifButton.setAttribute('aria-expanded', 'false');
      }
    }

    if (notifButton && notifMenu) { 
      notifButton.addEventListener('click', function(event) {
        event.stopPropagation(); 
        const isExpanded = notifMenu.classList.toggle('show'); 
        notifButton.setAttribute('aria-expanded', isExpanded);
        closeSettingsDropdown();
      });
    }

    // Notification Filtering Logic
    function filterNotifications(type, event) {
        if (event) {
            event.stopPropagation();
        }

        // Target the table body *inside the dropdown* specifically
        const notificationTableBody = document.querySelector("#notif-menu #notificationTableDropdown");
        if (!notificationTableBody) return; 

        const rows = notificationTableBody.querySelectorAll("tr");
        rows.forEach(row => row.style.display = "table-row"); 
        
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

        const tabButtons = document.querySelectorAll("#notif-menu .tabs button");
        tabButtons.forEach(btn => btn.classList.remove("active"));

  
        if(event && event.target && event.target.tagName === 'BUTTON') {
           event.target.classList.add("active");
        } 
        else {
           tabButtons.forEach(btn => {
              if (btn.getAttribute('onclick').includes(`'${type}'`)) {
                  btn.classList.add('active');
              }
           });
        }
    }


    //  Close Dropdowns on Outside Click
    window.addEventListener('click', function(event) {
        if (settingsMenu && settingsButton && !settingsButton.contains(event.target) && !settingsMenu.contains(event.target)) {
            closeSettingsDropdown();
        }
         
        if (notifMenu && notifButton && !notifButton.contains(event.target) && !notifMenu.contains(event.target)) {
           closeNotifDropdown();
        }
    });

    // Close Dropdowns on Escape Key 
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSettingsDropdown(); 
            closeNotifDropdown();    
        }
    });

    $(window).on('click', function(event) {
      $('.modal').each(function() {
          if (event.target == this) {
             if (!$(this).find('.save-btn').prop('disabled')) {
                 closeModal($(this).attr('id'));
             }
          }
      });
 });

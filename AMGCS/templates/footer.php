  <!-- Logout Confirmation Modal (Common element) -->
  <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
        <div class="modal-buttons">
        <button class="yes-btn" onclick="logout()">Yes</button>
        <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
        </div>
        </div>
    </div>

  <!-- Common JavaScript -->
  <script>
    // --- Sidebar Dropdown ---
    function toggleSidebarDropdown(e) {
      e.preventDefault();
      const section = e.currentTarget;
      const dropdown = section.nextElementSibling;
      const isShown = dropdown.classList.toggle('show');
      section.classList.toggle('active-dropdown', isShown);
    }

    // --- Header Dropdown Logic ---
    function setupDropdown(buttonId, menuId) { /* ... as before ... */ }
    function closeOtherDropdowns(currentMenuId) { /* ... as before ... */ }
    document.addEventListener('click', function (event) { /* ... as before ... */ });

    // --- Notification Filtering (Placeholder/Basic) ---
    function filterNotifications(type, event) { /* ... as before ... */ }

    // --- Initialize common elements on DOMContentLoaded ---
    document.addEventListener('DOMContentLoaded', () => {
        setupDropdown('settings-button', 'settings-menu');
        setupDropdown('notif-button', 'notif-menu');
        // Maybe load initial notifications via AJAX here
        // loadNotifications(); // Example function call
        filterNotifications('all'); // Set initial filter
    });

    // --- Assumed functions for Modal (might be in logout.js or here) ---
    function openLogoutModal(event) {
      if (event) event.preventDefault();
      const modal = document.getElementById('logoutModal');
      if(modal) modal.style.display = 'flex';
    }
    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      if(modal) modal.style.display = 'none';
    }
    // function logout() is likely in logout.js

  </script>

  <!-- Include external common scripts -->
  <script src="scripts/logout.js"></script> <!-- Adjust path if needed -->
  <!-- Include page-specific scripts if needed (can be done here or in the page file itself) -->
   <?php if (isset($pageJS) && is_array($pageJS)): ?>
    <?php foreach ($pageJS as $jsFile): ?>
      <script src="<?php echo htmlspecialchars($jsFile); ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>

</body>
</html>  <!-- Logout Confirmation Modal (Common element) -->
  <div id="logoutModal" class="modal">
        <div class="modal-content">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <button class="yes-btn" onclick="logout()">Yes</button>
            <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
        </div>
    </div>

  <!-- Common JavaScript -->
  <script>
    // --- Sidebar Dropdown ---
    function toggleSidebarDropdown(e) {
      e.preventDefault();
      const section = e.currentTarget;
      const dropdown = section.nextElementSibling;
      const isShown = dropdown.classList.toggle('show');
      section.classList.toggle('active-dropdown', isShown);
    }

    // --- Header Dropdown Logic ---
    function setupDropdown(buttonId, menuId) { /* ... as before ... */ }
    function closeOtherDropdowns(currentMenuId) { /* ... as before ... */ }
    document.addEventListener('click', function (event) { /* ... as before ... */ });

    // --- Notification Filtering (Placeholder/Basic) ---
    function filterNotifications(type, event) { /* ... as before ... */ }

    // --- Initialize common elements on DOMContentLoaded ---
    document.addEventListener('DOMContentLoaded', () => {
        setupDropdown('settings-button', 'settings-menu');
        setupDropdown('notif-button', 'notif-menu');
        // Maybe load initial notifications via AJAX here
        // loadNotifications(); // Example function call
        filterNotifications('all'); // Set initial filter
    });

    // --- Assumed functions for Modal (might be in logout.js or here) ---
    function openLogoutModal(event) {
      if (event) event.preventDefault();
      const modal = document.getElementById('logoutModal');
      if(modal) modal.style.display = 'flex';
    }
    function closeModal(modalId) {
      const modal = document.getElementById(modalId);
      if(modal) modal.style.display = 'none';
    }
    // function logout() is likely in logout.js

  </script>

  <!-- Include external common scripts -->
  <script src="scripts/logout.js"></script> <!-- Adjust path if needed -->
  <!-- Include page-specific scripts if needed (can be done here or in the page file itself) -->
   <?php if (isset($pageJS) && is_array($pageJS)): ?>
    <?php foreach ($pageJS as $jsFile): ?>
      <script src="<?php echo htmlspecialchars($jsFile); ?>"></script>
    <?php endforeach; ?>
  <?php endif; ?>

</body>
</html>
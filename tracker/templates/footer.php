</div>

    <!-- Logout Confirmation Modal -->
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

    <!-- Universal JavaScript for All Pages -->
    <script>
        // --- UNIVERSAL FUNCTIONS (Available on all pages) ---
        function openModal(modalId) {
            $('#' + modalId).css('display', 'flex');
        }

        function closeModal(modalId) {
            $('#' + modalId).css('display', 'none');
        }

        function openLogoutModal() {
            openModal('logoutModal');
        }

        function logout() {
            window.location.href = "sign_in.php"; 
        }

        function toggleSidebarDropdown(e) {
            e.preventDefault();
            const section = e.currentTarget;
            const dropdown = section.nextElementSibling;
            if (dropdown) {
                const isShown = dropdown.classList.toggle('show');
                section.classList.toggle('active-dropdown', isShown);
            }
        }
        
        // --- EVENT LISTENERS (This code runs on every page) ---
        $(document).ready(function() {
        
            // --- CONTROLS THE BELL & GEAR ICONS ---
            $('.header .icon-trigger').on('click', function(event) {
                event.stopPropagation();
                const dropdown = $(this).next('.dropdown-content');
                $('.dropdown-content').not(dropdown).removeClass('show');
                dropdown.toggleClass('show');
            });

            // --- CONTROLS THE LOGOUT BUTTON IN THE DROPDOWN ---
            $('#settings-menu .logout-trigger').on('click', function(event) {
                event.preventDefault();
                openLogoutModal();
            });

            // --- CONTROLS THE TABS IN THE NOTIFICATION DROPDOWN ---
            $('#notif-menu .tab-button').on('click', function(event) {
                event.stopPropagation();
                $('#notif-menu .tab-button').removeClass('active');
                $(this).addClass('active');
            });

            // --- CLOSES DROPDOWNS & MODALS WHEN CLICKING OUTSIDE ---
            $(window).on('click', function(event) {
                if ($(event.target).hasClass('modal')) {
                    closeModal($(event.target).attr('id'));
                }
                if (!$(event.target).closest('.icon-trigger, .dropdown-content').length) {
                    $('.dropdown-content').removeClass('show');
                }
            });
        });
    </script>

    <?php if (isset($pageJS) && is_array($pageJS)): ?>
        <?php foreach ($pageJS as $jsFile): ?>
            <script src="<?php echo htmlspecialchars($jsFile); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

</body>
</html>
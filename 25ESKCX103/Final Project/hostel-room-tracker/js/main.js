// Client-side UX & Interactive Behaviors - Hostel Room Request Tracker

document.addEventListener('DOMContentLoaded', function () {
  
  // 1. Auth Page Tabs Switcher (Student Login / Admin Login / Registration)
  const authTabs = document.querySelectorAll('[data-auth-tab]');
  if (authTabs.length > 0) {
    authTabs.forEach(tab => {
      tab.addEventListener('click', function (e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        authTabs.forEach(t => t.classList.remove('active'));
        // Add active to current
        this.classList.add('active');
        
        // Hide all panels
        const panels = document.querySelectorAll('.auth-panel');
        panels.forEach(panel => panel.classList.remove('active'));
        
        // Show selected panel
        const targetPanelId = this.getAttribute('data-auth-tab');
        const targetPanel = document.getElementById(targetPanelId);
        if (targetPanel) {
          targetPanel.classList.add('active');
        }
      });
    });
  }

  // 2. Room Browser Client-Side Filtering
  const roomSearchInput = document.getElementById('roomSearch');
  const filterHostel = document.getElementById('filterHostel');
  const filterType = document.getElementById('filterType');
  const filterStatus = document.getElementById('filterStatus');
  const roomCards = document.querySelectorAll('.room-card-item');

  function filterRooms() {
    if (!roomCards.length) return;
    
    const searchQuery = roomSearchInput ? roomSearchInput.value.toLowerCase().trim() : '';
    const hostelVal = filterHostel ? filterHostel.value : 'all';
    const typeVal = filterType ? filterType.value : 'all';
    const statusVal = filterStatus ? filterStatus.value : 'all';

    roomCards.forEach(card => {
      const roomNum = card.getAttribute('data-room-number').toLowerCase();
      const roomHostel = card.getAttribute('data-hostel-id');
      const roomType = card.getAttribute('data-room-type');
      const roomStatus = card.getAttribute('data-room-status'); // Available, Full, Maintenance
      
      let match = true;

      // Filter by Search Query (Room Number)
      if (searchQuery && !roomNum.includes(searchQuery)) {
        match = false;
      }
      // Filter by Hostel
      if (hostelVal !== 'all' && roomHostel !== hostelVal) {
        match = false;
      }
      // Filter by Room Type (AC/Non-AC)
      if (typeVal !== 'all' && roomType !== typeVal) {
        match = false;
      }
      // Filter by Availability Status
      if (statusVal !== 'all' && roomStatus !== statusVal) {
        match = false;
      }

      if (match) {
        card.style.display = 'block';
        card.classList.add('animated-fade-in');
      } else {
        card.style.display = 'none';
      }
    });

    // Check if no rooms are visible and show helper notice
    const visibleCards = Array.from(roomCards).filter(c => c.style.display !== 'none');
    const noResultsMsg = document.getElementById('noRoomsAlert');
    if (noResultsMsg) {
      noResultsMsg.style.display = visibleCards.length === 0 ? 'block' : 'none';
    }
  }

  // Bind room filters
  if (roomSearchInput) roomSearchInput.addEventListener('input', filterRooms);
  if (filterHostel) filterHostel.addEventListener('change', filterRooms);
  if (filterType) filterType.addEventListener('change', filterRooms);
  if (filterStatus) filterStatus.addEventListener('change', filterRooms);

  // 3. Room Request Form Modal Data Transfer
  const requestRoomModal = document.getElementById('requestRoomModal');
  if (requestRoomModal) {
    requestRoomModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      
      const roomId = button.getAttribute('data-room-id');
      const roomNumber = button.getAttribute('data-room-number');
      const hostelName = button.getAttribute('data-hostel-name');
      const roomPrice = button.getAttribute('data-room-price');
      const roomType = button.getAttribute('data-room-type');
      const roomCapacity = button.getAttribute('data-room-capacity');

      // Populate elements in modal
      document.getElementById('modal_room_id').value = roomId;
      document.getElementById('modal_room_details').innerText = `${hostelName} - Room ${roomNumber} (${roomType}, ${roomCapacity} Sharing)`;
      document.getElementById('modal_room_price').innerText = roomPrice;
    });
  }

  // 4. Admin Request Actions Modal Details Loader
  const approveRequestModal = document.getElementById('approveRequestModal');
  if (approveRequestModal) {
    approveRequestModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const requestId = button.getAttribute('data-request-id');
      const studentName = button.getAttribute('data-student-name');
      const roomNumber = button.getAttribute('data-room-number');
      const hostelName = button.getAttribute('data-hostel-name');

      document.getElementById('approve_request_id').value = requestId;
      document.getElementById('approve_details').innerText = `Approve ${studentName}'s request for Room ${roomNumber} (${hostelName})?`;
    });
  }

  const rejectRequestModal = document.getElementById('rejectRequestModal');
  if (rejectRequestModal) {
    rejectRequestModal.addEventListener('show.bs.modal', function (event) {
      const button = event.relatedTarget;
      const requestId = button.getAttribute('data-request-id');
      const studentName = button.getAttribute('data-student-name');
      const roomNumber = button.getAttribute('data-room-number');
      const hostelName = button.getAttribute('data-hostel-name');

      document.getElementById('reject_request_id').value = requestId;
      document.getElementById('reject_details').innerText = `Reject ${studentName}'s request for Room ${roomNumber} (${hostelName})?`;
    });
  }

  // 5. Form Auto-Validation Styling Boost (Bootstrap Styles)
  const forms = document.querySelectorAll('.needs-validation');
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      form.classList.add('was-validated');
    }, false);
  });
});

     // User data storage
     let users = JSON.parse(localStorage.getItem('makupay_users')) || {};
     let currentUser = null;
     
     // DOM Elements
     const authSection = document.getElementById('auth-section');
     const dashboardSection = document.getElementById('dashboard-section');
     const loginForm = document.getElementById('login-form');
     const registerForm = document.getElementById('register-form');
     const paymentModal = document.getElementById('payment-modal');
     const usernameDisplay = document.getElementById('username-display');
     const totalSaved = document.getElementById('total-saved');
     const currentStreak = document.getElementById('current-streak');
     const totalDays = document.getElementById('total-days');
     const paymentAmount = document.getElementById('payment-amount');
     const paymentType = document.getElementById('payment-type');
     
     // Show auth tab
     function showAuthTab(tab) {
       document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
       document.querySelectorAll('.auth-form').forEach(form => form.classList.remove('active'));
       
       if (tab === 'login') {
         document.querySelector('.tab-btn:nth-child(1)').classList.add('active');
         loginForm.classList.add('active');
       } else {
         document.querySelector('.tab-btn:nth-child(2)').classList.add('active');
         registerForm.classList.add('active');
       }
     }
     
     // Register function
     function register() {
       const name = document.getElementById('register-name').value;
       const email = document.getElementById('register-email').value;
       const phone = document.getElementById('register-phone').value;
       const pin = document.getElementById('register-pin').value;
       
       if (!name || !email || !phone || !pin) {
         alert('Please fill in all fields');
         return;
       }
       
       if (pin.length !== 4) {
         alert('PIN must be 4 digits');
         return;
       }
       
       if (users[email]) {
         alert('User with this email already exists');
         return;
       }
       
       // Save user data
       users[email] = {
         name,
         email,
         phone,
         pin,
         totalSaved: 0,
         currentStreak: 0,
         totalDays: 0,
         transactions: []
       };
       
       localStorage.setItem('makupay_users', JSON.stringify(users));
       
       alert('Registration successful! Please login with your credentials.');
       showAuthTab('login');
       
       // Clear registration form
       document.getElementById('register-name').value = '';
       document.getElementById('register-email').value = '';
       document.getElementById('register-phone').value = '';
       document.getElementById('register-pin').value = '';
     }
     
     // Login function
     function login() {
       const email = document.getElementById('login-email').value;
       const pin = document.getElementById('login-pin').value;
       
       if (!email || !pin) {
         alert('Please fill in all fields');
         return;
       }
       
       if (!users[email] || users[email].pin !== pin) {
         alert('Invalid email or PIN');
         return;
       }
       
       currentUser = users[email];
       updateDashboard();
       authSection.classList.remove('active');
       dashboardSection.classList.add('active');
       
       // Clear login form
       document.getElementById('login-email').value = '';
       document.getElementById('login-pin').value = '';
     }
     
     // Logout function
     function logout() {
       currentUser = null;
       dashboardSection.classList.remove('active');
       authSection.classList.add('active');
     }
     
     // Update dashboard with user data
     function updateDashboard() {
       usernameDisplay.textContent = currentUser.name;
       totalSaved.textContent = `₦${currentUser.totalSaved.toLocaleString()}`;
       currentStreak.textContent = `${currentUser.currentStreak} days`;
       totalDays.textContent = currentUser.totalDays;
     }
     
     // Show payment modal
     function showPaymentModal() {
       const amount = document.getElementById('contribution-amount').value;
       const type = document.getElementById('contribution-type').value;
       
       if (!amount || amount < 1000) {
         alert('Minimum contribution is ₦1000');
         return;
       }
       
       paymentAmount.textContent = `₦${parseInt(amount).toLocaleString()}`;
       paymentType.textContent = type;
       paymentModal.classList.add('active');
     }
     
     // Close payment modal
     function closePaymentModal() {
       paymentModal.classList.remove('active');
     }
     
     // Process payment
     function processPayment() {
       const amount = parseInt(document.getElementById('contribution-amount').value);
       const type = document.getElementById('contribution-type').value;
       
       // Update user data
       currentUser.totalSaved += amount;
       currentUser.currentStreak += 1;
       currentUser.totalDays += 1;
       
       // Add transaction
       currentUser.transactions.push({
         type: 'contribution',
         amount,
         date: new Date().toISOString(),
         contributionType: type
       });
       
       // Save updated user data
       users[currentUser.email] = currentUser;
       localStorage.setItem('makupay_users', JSON.stringify(users));
       
       // Update dashboard
       updateDashboard();
       
       // Show success message
       const statusElement = document.getElementById('today-status');
       statusElement.textContent = `Successfully contributed ₦${amount.toLocaleString()} as ${type} contribution!`;
       statusElement.className = 'status-message success';
       
       // Close modal and clear form
       closePaymentModal();
       document.getElementById('contribution-amount').value = '';
     }
     
     // Withdraw funds
     function withdrawFunds() {
       const bank = document.getElementById('withdrawal-bank').value;
       const amount = parseInt(document.getElementById('withdrawal-amount').value);
       
       if (!bank) {
         alert('Please select a bank');
         return;
       }
       
       if (!amount || amount <= 0) {
         alert('Please enter a valid amount');
         return;
       }
       
       if (amount > currentUser.totalSaved) {
         alert('Insufficient funds');
         return;
       }
       
       // Update user data
       currentUser.totalSaved -= amount;
       
       // Add transaction
       currentUser.transactions.push({
         type: 'withdrawal',
         amount,
         date: new Date().toISOString(),
         bank
       });
       
       // Save updated user data
       users[currentUser.email] = currentUser;
       localStorage.setItem('makupay_users', JSON.stringify(users));
       
       // Update dashboard
       updateDashboard();
       
       alert(`Withdrawal of ₦${amount.toLocaleString()} to ${bank} bank was successful!`);
       
       // Clear form
       document.getElementById('withdrawal-amount').value = '';
       document.getElementById('withdrawal-bank').selectedIndex = 0;
     }
     
     // Initialize the app
     function init() {
       // Check if user is already logged in
       const loggedInUser = localStorage.getItem('makupay_current_user');
       if (loggedInUser) {
         currentUser = JSON.parse(loggedInUser);
         updateDashboard();
         authSection.classList.remove('active');
         dashboardSection.classList.add('active');
       }
     }
     
     // Run initialization when page loads
     window.onload = init;
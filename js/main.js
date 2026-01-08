document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault(); 
    const data = {
      name: document.getElementById('name').value,
      phone: document.getElementById('phone').value,
      password: document.getElementById('password').value
    };
    fetch('https://script.google.com/macros/s/AKfycby2iIoTKBuldvYi5Olh1eM4SQGH_MIoKlDqFnqIg81a3ow3aItaP_NQfOH3m8w1chqt/exec', {
      method: 'POST',
      body: JSON.stringify(data),
      headers: { 'Content-Type': 'application/json' }
    }).then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          window.location.href = 'testcontent.html'; // Redirect to the main content page
        }
      });
  });




/** 

// Function to get URL query parameters
function getQueryParams() {
    const urlParams = new URLSearchParams(window.location.search);
    return {
        subject: urlParams.get('subject')
    };
}
*/

        async function showTeachers(subject) {
  try {
    const response = await fetch(`${subject}/${subject}.html`, { method: 'HEAD' }); // Use 'HEAD' to check if the page exists

    if (response.ok) {
      // If the page exists (status 200-299), redirect to the subject page
      location.href = `${subject}/${subject}.html`;
    } else {
      // If the page does not exist (status 404 or other), redirect to contentnotavailable.html
      location.href = 'notavilable.html';
    }
  } catch (error) {
    // In case of any network errors, redirect to contentnotavailable.html
    location.href = 'notavailable.html';
    console.error('Error checking page availability:', error);
  }
}



window.onload = function() {
  const userContentIDs = JSON.parse(sessionStorage.getItem('userContentIDs')) || [];

  document.querySelectorAll('.content-section').forEach(section => {
    const contentID = section.getAttribute('data-id');
    if (!userContentIDs.includes(contentID)) {
      section.style.display = 'none'; // Hide content not accessible to the user
    }
  });
};


// Retrieve the phone number from localStorage
const userPhone = localStorage.getItem('userPhone');

// Check if a phone number is stored, and display it
if (userPhone) {
    document.getElementById('userPhoneDisplay').textContent = `Welcome, ${userPhone}`;
}




async function incrementClickCount(sheetName, elementId) {
  try {
    // Replace with your Web App URL
    const url = 'https://script.google.com/macros/s/AKfycbw3_8c0p7nKQdcOmLuHYunKvOHmwAffiyhB0-ZKYmVIUp4vHRCZaxUjfRHyXNVf-gqCzw/exec';

    // Make the request
    const response = await fetch(`${url}?sheetName=${encodeURIComponent(sheetName)}&elementId=${encodeURIComponent(elementId)}`, {
      method: 'GET'
    });

    // Check if the response is ok
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }

    // Parse the response as JSON
    const data = await response.json();

    // Check the response data
    if (data.status === 'error') {
      console.error(`Error from server: ${data.message}`);
    } else {
      console.log('Click count incremented successfully:', data); // Success message
    }
  } catch (error) {
    console.error('Error incrementing click count:', error);
  }
}


// Usage
incrementClickCount('YourSheetName', 'A1'); // Call the function with your sheet name and cell reference




const logoutBtn = document.getElementById('logout-btn');


  // Check if a phone number exists in local storage
  console.log('Phone number from local storage:', userPhone);


function logout() {
  console.log('Logout button clicked');
  localStorage.removeItem('userPhone'); // Clear the phone number from local storage
  window.location.href = 'login.html'; // Redirect to the login page
}

window.onload = function() {
  console.log('Window fully loaded');

  // Check if a phone number exists in local storage
  const userPhone = localStorage.getItem('userPhone'); // Ensure this matches how you set it in local storage
  console.log('Phone number from local storage:', userPhone);

  // Get the logout button element
  const logoutBtn = document.getElementById('logout-btn');
  console.log('Logout button element:', logoutBtn); // Check if button is found

  if (!logoutBtn) {
      console.error('Logout button not found');
      return; // Stop execution if the button is not found
  }

  // Function to handle logout
  function logout() {
      console.log('Logout button clicked');
      localStorage.removeItem('userPhone'); // Clear the phone number from local storage
      window.location.href = 'login.html'; // Redirect to the login page
  }

  // Check if userPhone exists
  if (userPhone) {
      // If the phone number exists, make the logout button visible
      logoutBtn.style.display = 'flex'; // Change to 'block' if needed
      console.log('Logout button made visible');

      // Add event listener to the logout button
      logoutBtn.addEventListener('click', logout);
  } else {
      // If no phone number is found, hide the logout button
      logoutBtn.style.display = 'none';
      console.log('Logout button hidden');
  }
};

console.log (userPhone);
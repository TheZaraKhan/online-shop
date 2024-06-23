document.addEventListener("DOMContentLoaded", () => {
  initializeIcons();
  checkLoginStatus();
});

// Select elements for login, register, logout, user icon, and username display
const login = document.querySelector(".login");
const register = document.querySelector(".register");
const logout = document.querySelector(".logout");
const userIcon = document.querySelector(".user-icon");
const username = document.querySelector(".username");

// Add click event listener to the login button
login.addEventListener("click", () => {
  displayOverlay(loginForm());
});
// Add click event listener to the register button
register.addEventListener("click", () => {
  displayOverlay(registerForm());
});

// Add click event listener to the logout button
logout.addEventListener("click", () => {
  logoutUser();
});

// Initialize the visibility of the icons when the page loads
function initializeIcons() {
  // Show login and register buttons, hide logout, user icon, and username
  displayIcons(login, true);
  displayIcons(register, true);
  displayIcons(logout, false);
  displayIcons(userIcon, false);
  displayIcons(username, false);
}

// Display or hide the icons
function displayIcons(element, flag) {
  flag ? (element.style.display = "flex") : (element.style.display = "none");
}

// ------------ login form start here ---------------
// Create and return the login form element
function loginForm() {
  const form = document.createElement("form");
  form.className = "login-form flex flex-col p-6";
  form.action = "http://localhost:8081/user/backend/login.php";
  form.method = "POST";

  // Create form header
  const header = document.createElement("h1");
  header.className =
    "text-3xl -mt-3 mb-6 font-semibold text-gray-800 text-center";
  header.textContent = "LOGIN";

  // Create username input field
  const userInput = document.createElement("input");
  userInput.className = "mb-6 border rounded border-gray-400 p-2";
  userInput.type = "text";
  userInput.name = "username";
  userInput.placeholder = "Username";

  // Create password input field
  const passwordInput = document.createElement("input");
  passwordInput.className = "mb-6 border rounded border-gray-400 p-2";
  passwordInput.type = "password";
  passwordInput.name = "password";
  passwordInput.placeholder = "Password";

  // Create error message element, initially hidden
  const errorMsg = document.createElement("p");
  errorMsg.className = "text-red-500 mb-3";
  errorMsg.textContent = "invalid username or password";
  errorMsg.style.display = "none";

  // Create submit button
  const submitButton = document.createElement("input");
  submitButton.className =
    "bg-cyan-800 text-white p-2 w-48 mx-auto rounded hover:bg-cyan-600";
  submitButton.type = "submit";
  submitButton.value = "Login";
  submitButton.addEventListener("click", userLoginRequest);

  // Function to handle the login request
  function userLoginRequest(e) {
    e.preventDefault(); // Prevent form from submitting the default way
    const form = document.querySelector(".login-form");
    const formData = new FormData(form);
    fetch("http://localhost:8081/user/backend/login.php", {
      method: "POST",
      mode: "cors",
      credentials: "include",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.user) {
          localStorage.setItem("username", data.user); // Save username to local storage

          // Set the content of the overlay
          const content = document.querySelector(".overlay-content");
          content.innerHTML = "Login Successful!"; // Set the content of the overlay
          content.style.color = "green";
          content.style.fontSize = "20px";

          // Update the UI after a delay
          setTimeout(() => {
            loggedIn(data.user); // Call loggedIn function to update UI
            updateCart();
            removeOverlay(); // Remove overlay after login
          }, 1000);
        } else {
          errorMsg.style.display = "block";
        }
      })
      .catch((error) => {
        console.log(error);
        errorMsg.style.display = "block"; // Show error message if there is an error
      });
  }

  // append

  form.appendChild(header);
  form.appendChild(userInput);
  form.appendChild(passwordInput);
  form.appendChild(errorMsg);
  form.appendChild(submitButton);

  return form;
}

function loggedIn(user) {
  displayIcons(login, false);
  displayIcons(register, false);
  displayIcons(logout, true);
  displayIcons(userIcon, true);
  displayIcons(username, true);
  username.textContent = user;
  removeOverlay(); // Remove overlay on successful login
}

// Function to check login status
function checkLoginStatus() {
  // Send a request to the server to check if the user is logged in
  fetch("http://localhost:8081/user/backend/login.php", {
    method: "GET",
    mode: "cors",
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.user) {
        // If the user is logged in, update the UI
        loggedIn(data.user);
      }
    })
    .catch((error) => {
      console.error("Error checking login status:", error);
    });
}
// ------------- login form end here ---------------

// --------------- register form start here ---------------

function registerForm() {
  const form = document.createElement("form");
  form.className = "signup-form flex flex-col p-6";
  form.action = "http://localhost:8081/user/backend/register.php";
  form.method = "POST";

  // Create form header
  const header = document.createElement("h1");
  header.className =
    "text-3xl -mt-3 mb-6 font-semibold text-gray-800 text-center";
  header.textContent = "Create an account";

  // First name input field
  const firstNameInput = document.createElement("input");
  firstNameInput.className = "mb-6 border rounded border-gray-400 p-2";
  firstNameInput.type = "text";
  firstNameInput.name = "firstName";
  firstNameInput.placeholder = "First Name";

  // Last name input field
  const lastNameInput = document.createElement("input");
  lastNameInput.className = "mb-6 border rounded border-gray-400 p-2";
  lastNameInput.type = "text";
  lastNameInput.name = "lastName";
  lastNameInput.placeholder = "Last Name";

  // Username input field
  const userInput = document.createElement("input");
  userInput.className = "mb-6 border rounded border-gray-400 p-2";
  userInput.type = "text";
  userInput.name = "username";
  userInput.placeholder = "Username";

  // Email input field
  const emailInput = document.createElement("input");
  emailInput.className = "mb-6 border rounded border-gray-400 p-2";
  emailInput.type = "email";
  emailInput.name = "email";
  emailInput.placeholder = "Email";

  // Password input field
  const passwordInput = document.createElement("input");
  passwordInput.className = "mb-6 border rounded border-gray-400 p-2";
  passwordInput.type = "password";
  passwordInput.name = "password";
  passwordInput.placeholder = "Password";

  // Error message element, initially hidden
  const errorMsg = document.createElement("p");
  errorMsg.className = "text-red-500 mb-3";
  errorMsg.style.display = "none";

  // Create submit button
  const submitButton = document.createElement("input");
  submitButton.className =
    "bg-cyan-800 text-white p-2 w-48 mx-auto rounded hover:bg-cyan-600";
  submitButton.type = "submit";
  submitButton.value = "Signup";
  submitButton.addEventListener("click", userSignupRequest);

  // Function to handle the signup request
  function userSignupRequest(e) {
    e.preventDefault(); // Prevent form from submitting the default way

    // Validate the form inputs
    if (!validateForm()) {
      errorMsg.style.display = "block"; // Show error message if validation fails
      return;
    }

    // Hide error message if form is valid
    errorMsg.style.display = "none";

    const formData = new FormData(form);
    fetch("http://localhost:8081/user/backend/register.php", {
      method: "POST",
      mode: "cors",
      credentials: "include",
      body: formData,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          // Handle successful registration (e.g., redirect to login page)
          userRegistered();
        } else {
          // Show server-side error message
          errorMsg.textContent = data.error;
          errorMsg.style.display = "block";
        }
      })
      .catch((error) => {
        console.log(error);
        errorMsg.textContent = "An error occurred. Please try again.";
        errorMsg.style.display = "block"; // Show error message if there is an error
      });
  }

  // Function to validate the form inputs
  function validateForm() {
    let isValid = true;
    errorMsg.textContent = "";

    // Validate first name
    if (firstNameInput.value.trim() === "") {
      errorMsg.textContent += "First name is required. ";
      isValid = false;
    }

    // Validate last name
    if (lastNameInput.value.trim() === "") {
      errorMsg.textContent += "Last name is required. ";
      isValid = false;
    }

    // Validate username
    if (userInput.value.trim() === "") {
      errorMsg.textContent += "Username is required. ";
      isValid = false;
    }

    // Validate email
    if (emailInput.value.trim() === "") {
      errorMsg.textContent += "Email is required. ";
      isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value)) {
      errorMsg.textContent += "Invalid email format. ";
      isValid = false;
    }

    // Validate password
    if (passwordInput.value.trim() === "") {
      errorMsg.textContent += "Password is required. ";
      isValid = false;
    } else if (passwordInput.value.length < 6) {
      errorMsg.textContent += "Password must be at least 6 characters long. ";
      isValid = false;
    }

    return isValid;
  }

  form.appendChild(header);
  form.appendChild(firstNameInput);
  form.appendChild(lastNameInput);
  form.appendChild(userInput);
  form.appendChild(emailInput);
  form.appendChild(passwordInput);
  form.appendChild(errorMsg);
  form.appendChild(submitButton);

  return form;
}

function userRegistered() {
  // Handle successful registration (e.g., redirect to login page)
  console.log("User registered successfully");

  // Set the content of the overlay
  const modal = document.querySelector(".overlay-content");
  modal.innerHTML = ""; // Set the content of the overlay
  const div = document.createElement("div");
  div.className = "text-center";

  const p = document.createElement("p");
  p.textContent = "Account created successfully";
  p.className = "text-green-500 text-xl text-center py-4 px-3";

  const loginButton = document.createElement("button");
  loginButton.className =
    "bg-cyan-800 mx-auto text-white text-center px-3 py-4 w-48 mx-auto rounded hover:bg-cyan-600";
  loginButton.textContent = "Login";

  loginButton.addEventListener("click", () => {
    displayOverlay(loginForm());
  });

  div.appendChild(p);
  div.appendChild(loginButton);
  modal.appendChild(div);

  console.log("Registration successful");
}

// -------------- register form end here ---------------
// -------------- logout start here ---------------
function logoutUser() {
  fetch("http://localhost:8081/user/backend/logout.php", {
    method: "POST",
    mode: "cors",
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Clear the username from local storage
        localStorage.removeItem("username");
        updateCart();

        // Reset the UI to logged-out state
        initializeIcons();
      } else {
        console.error("Logout failed");
      }
    })
    .catch((error) => {
      console.error("Error during logout:", error);
    });
}
// --------------- logout end here ---------------

// Function to display an overlay

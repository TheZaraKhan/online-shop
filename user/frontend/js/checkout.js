document.addEventListener("DOMContentLoaded", () => {
  // Check for #checkout-done in URL hash
  if (window.location.hash === "#checkout-done") {
    displayOverlay(checkoutCompleteMessage());
  }
});

function checkout() {
  console.log("show checkout");
  displayOverlay(checkoutForm());
}

function checkoutForm() {
  const form = document.createElement("form");

  const header = document.createElement("h2");
  header.textContent = "Checkout";
  header.className = "text-2xl font-bold mb-6 text-center text-gray-800";

  const address = document.createElement("input");
  address.type = "text";
  address.className = "w-full border border-gray-300 rounded py-2 px-3 mb-3";
  address.placeholder = "Address";
  address.name = "address";

  const city = document.createElement("input");
  city.type = "text";
  city.className = "w-full border border-gray-300 rounded py-2 px-3 mb-3";
  city.placeholder = "City";
  city.name = "city";

  const province = document.createElement("input");
  province.type = "text";
  province.className = "w-full border border-gray-300 rounded py-2 px-3 mb-3";
  province.placeholder = "Province";
  province.name = "province";

  const postcode = document.createElement("input");
  postcode.type = "text";
  postcode.className = "w-full border border-gray-300 rounded py-2 px-3 mb-3";
  postcode.placeholder = "Postcode";
  postcode.name = "postcode";

  const errorMsg = document.createElement("p");
  errorMsg.className = "text-red-500";
  errorMsg.style.display = "none";

  const checkoutBtn = document.createElement("button");
  checkoutBtn.className =
    "bg-cyan-800 text-white py-2 px-5 mt-3 rounded hover:bg-cyan-600 transition-colors duration-300";
  checkoutBtn.textContent = "Checkout";
  checkoutBtn.type = "submit";

  form.appendChild(header);
  form.appendChild(address);
  form.appendChild(city);
  form.appendChild(province);
  form.appendChild(postcode);
  form.appendChild(errorMsg);
  form.appendChild(checkoutBtn);

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(form);
    const payload = new URLSearchParams();
    for (const [key, value] of formData.entries()) {
      payload.append(key, value);
    }

    console.log("Form Data Submitted:", payload.toString()); // Add this line for debugging

    fetch("http://localhost:8081/user/backend/checkout.php", {
      method: "POST",
      body: payload,
    })
      .then((response) => response.json())
      .then((data) => {
        console.log("Checkout response data:", data); // Add this line for debugging
        if (data.status === "success" && data.url) {
          console.log("Redirecting to:", data.url); // Add this line for debugging
          window.location.href = data.url;
        } else {
          errorMsg.textContent = "An error occurred. Please try again.";
          errorMsg.style.display = "block";
        }
      })
      .catch((error) => {
        console.error("Error submitting form:", error);
        errorMsg.textContent = "An error occurred. Please try again.";
        errorMsg.style.display = "block";
      });
  });

  fetch("http://localhost:8081/user/backend/login.php?q=check_status", {
    method: "GET",
    credentials: "include",
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.user) {
        console.log("Logged in user:", data.user);
      } else {
        const name = document.createElement("input");
        name.type = "text";
        name.className = "w-full border border-gray-300 rounded py-2 px-3 mb-3";
        name.placeholder = "Full Name ";
        name.name = "name";
        form.insertBefore(name, address);

        const email = document.createElement("input");
        email.type = "email";
        email.className =
          "w-full border border-gray-300 rounded py-2 px-3 mb-3";
        email.placeholder = "Email";
        email.name = "email";
        form.insertBefore(email, address);
      }
    })
    .catch((error) => {
      console.error("Error fetching user data:", error);
    });

  return form;
}

function checkoutCompleteMessage() {
  const messageDiv = document.createElement("div");
  messageDiv.className = "checkout-complete-message";
  messageDiv.innerHTML =
    "<h2>Checkout Complete</h2><p>Thank you for your purchase!</p>";
  return messageDiv;
}

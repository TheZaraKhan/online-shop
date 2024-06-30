document.addEventListener("DOMContentLoaded", () => {
  updateCart();
});

const localCart = {
  cart: null,
  total: 0,
  length: 0,
};

function updateCart() {
  fetch(`${domain}/user/backend/cart.php`)
    .then((response) => response.json())
    .then((data) => {
      responseUpdateCart(data);
    })
    .catch((error) => {
      console.error("Error fetching cart:", error);
    });
}

function responseUpdateCart(data) {
  const { total, ...cart } = data.cart;
  const cartCount = document.getElementById("cart-count");
  const count = Object.keys(cart).length;

  cartCount.textContent = count;
  if (count === 0) {
    cartCount.classList.add("hidden");
  } else {
    cartCount.classList.remove("hidden");
  }

  localCart.cart = cart;
  localCart.total = total;
  localCart.length = count;

  // showCart();
  //   console.log(cart, total, count);
}

function addProductToCart() {
  // select quantity
  const quantity = document.getElementById("quantity").textContent;

  const payload = new URLSearchParams();
  payload.append("id", this.id);
  payload.append("name", this.name);
  payload.append("image", this.image);
  payload.append("stock", this.stock);
  payload.append("quantity", quantity);

  fetch(`${domain}/user/backend/cart.php`, {
    method: "POST",
    body: payload,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Backend response:", data);
      responseUpdateCart(data);
    })
    .catch((error) => {
      console.error("Error adding product to cart:", error);
    });
}

// show cart
function showCart() {
  if (localCart.length === 0) {
    displayOverlay(cartEmpty());
  } else {
    displayOverlay(cartBox());
  }
}

// cart empty
function cartEmpty() {
  const cartBoxContent = document.createElement("div");
  cartBoxContent.className = "overflow-y-auto h-full w-full ";
  cartBoxContent.innerHTML = "Your cart is empty";
  return cartBoxContent;
}
// cart content
function cartBox() {
  const cartBoxContent = document.createElement("div");
  cartBoxContent.className = "overflow-y-auto h-full w-full ";

  // Add cart items to the cart box
  const cartItems = document.createElement("div");
  cartItems.className = "w-full flex "; // Adjust the grid columns as needed

  const table = document.createElement("table");
  table.className = "table-auto text-center w-full text-center";

  const thead = document.createElement("thead");
  const headerRow = document.createElement("tr");
  headerRow.className = "border-b border-gray-300";
  const thdelete = document.createElement("th");
  thdelete.className = "pb-3 w-10";
  thdelete.textContent = " ";
  const thImage = document.createElement("th");
  thImage.className = "pb-3 w-1/5";
  thImage.textContent = "Item";
  const thProduct = document.createElement("th");
  thProduct.className = "pb-3";
  thProduct.textContent = " ";
  const thQuantity = document.createElement("th");
  thQuantity.className = "pb-3";
  thQuantity.textContent = "Quantity";
  const thPrice = document.createElement("th");
  thPrice.className = "pb-3";
  thPrice.textContent = "Price";

  headerRow.appendChild(thdelete);
  headerRow.appendChild(thImage);
  headerRow.appendChild(thProduct);
  headerRow.appendChild(thQuantity);
  headerRow.appendChild(thPrice);
  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement("tbody");

  for (const [id, product] of Object.entries(localCart.cart)) {
    const { image, name, quantity, price, stock } = product;

    // Create table row
    const tr = document.createElement("tr");
    tr.className = "border-b border-gray-300";

    // Create table data elements
    const tdDelete = document.createElement("td");
    const deleteButton = document.createElement("button");
    const deleteIcon = document.createElement("i");
    deleteIcon.className = "fa fa-trash text-red-500 cursor-pointer";
    deleteButton.appendChild(deleteIcon);
    deleteButton.addEventListener("click", () => {
      deleteProduct(id); // Pass the product ID to the deleteProduct function
    });

    tdDelete.appendChild(deleteButton);
    const tdImage = document.createElement("td");
    const img = document.createElement("img");
    img.src = image;
    img.alt = name;
    img.className = "cart-item-image";
    tdImage.appendChild(img);

    const tdProduct = document.createElement("td");
    tdProduct.textContent = name;

    const tdQuantity = document.createElement("td");
    // Create quantity container
    const quantityContainer = document.createElement("div");
    quantityContainer.className = "flex items-center justify-center";

    // Create minus button
    const minusButton = document.createElement("button");
    minusButton.textContent = "-";
    minusButton.className = "bg-gray-200 py-1 px-2 rounded-l";
    minusButton.addEventListener("click", () => {
      const currentQuantity = parseInt(quantityText.textContent);
      if (currentQuantity > 1) {
        quantityText.textContent = currentQuantity - 1;
        // Update the cart and total here if needed
        updateItemTotal(currentQuantity - 1, id); // Update total price
      }
    });

    // Create quantity text element
    const quantityText = document.createElement("p");
    quantityText.textContent = quantity; // Set this to the current quantity
    quantityText.className =
      "w-8 border-2 border-gray-200 py-1 text-sm text-center m-0";

    // Create plus button
    const plusButton = document.createElement("button");
    plusButton.textContent = "+";
    plusButton.className = "bg-gray-200 py-1 px-2 rounded-r";
    plusButton.addEventListener("click", () => {
      const currentQuantity = parseInt(quantityText.textContent);
      if (currentQuantity < stock) {
        quantityText.textContent = currentQuantity + 1;
        // Update the cart and total here if needed
        updateItemTotal(currentQuantity + 1, id); // Update total price
      }
    });

    quantityContainer.appendChild(minusButton);
    quantityContainer.appendChild(quantityText);
    quantityContainer.appendChild(plusButton);

    // Append quantity container to tdQuantity (replace tdQuantity with your actual container)
    tdQuantity.appendChild(quantityContainer);

    const tdPrice = document.createElement("td");
    tdPrice.textContent = price;
    tr.appendChild(tdDelete);
    tr.appendChild(tdImage);
    tr.appendChild(tdProduct);
    tr.appendChild(tdQuantity);
    tr.appendChild(tdPrice);
    tbody.appendChild(tr);
  }

  const totalRow = document.createElement("tr");
  totalRow.className = "";
  const td = document.createElement("td");
  const td1 = document.createElement("td");
  const td2 = document.createElement("td");

  const tdTotal = document.createElement("td");
  tdTotal.className = "font-bold py-10";
  tdTotal.textContent = "Total";
  const tdTotalValue = document.createElement("td");
  tdTotalValue.className = "font-bold py-10";
  tdTotalValue.textContent = localCart.total;
  totalRow.appendChild(td);
  totalRow.appendChild(td1);
  totalRow.appendChild(td2);
  totalRow.appendChild(tdTotal);
  totalRow.appendChild(tdTotalValue);
  tbody.appendChild(totalRow);

  table.appendChild(tbody);
  cartItems.appendChild(table);
  cartBoxContent.appendChild(cartItems);
  const options = document.createElement("div");
  options.className = "flex w-full  justify-between";
  const continueBtn = document.createElement("button");
  continueBtn.className =
    "bg-cyan-800 text-white py-2 px-5 mt-3 rounded hover:bg-cyan-600 transition-colors duration-300";
  continueBtn.textContent = "Continue Shopping";

  const checkoutBtn = document.createElement("button");
  checkoutBtn.className =
    "bg-cyan-800 text-white py-2 float-right px-5 mt-3 rounded hover:bg-cyan-600 transition-colors duration-300";
  checkoutBtn.textContent = "Checkout";

  checkoutBtn.addEventListener("click", checkout);

  options.appendChild(continueBtn);
  options.appendChild(checkoutBtn);
  cartBoxContent.appendChild(options);

  return cartBoxContent;
}

function updateItemTotal(quantity, id) {
  const payload = new URLSearchParams();
  payload.append("quantity", quantity);
  payload.append("id", id);

  //   console.log(payload, id);

  fetch(`${domain}/user/backend/cart.php`, {
    method: "PATCH",
    body: payload,
  })
    .then((response) => response.json())
    .then((data) => {
      //   console.log(data);
      updateQuantity(data);
    })
    .catch((error) => {
      console.error("Error updating item total:", error);
    });
}

updateQuantity = (data) => {
  responseUpdateCart(data);
  showCart();
};

// Modify the deleteProduct function to accept an ID parameter
function deleteProduct(id) {
  const payload = new URLSearchParams();
  payload.append("id", id);
  fetch(`${domain}/user/backend/cart.php`, {
    method: "DELETE",
    body: payload,
  })
    .then((response) => response.json())
    .then((data) => {
      //   console.log(data);
      responseUpdateCart(data);
      showCart();
    })
    .catch((error) => {
      console.error("Error deleting product:", error);
    });
}

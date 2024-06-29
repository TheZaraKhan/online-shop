// Fetch and display products when the DOM content is loaded
document.addEventListener("DOMContentLoaded", requestProducts);

/**
 * Fetch products data from the server and dispatch it to the appropriate handlers.
 */
function requestProducts() {
  // Fetch products data from the server
  fetch("http://localhost:8081/user/backend/catalogue.php")
    .then((res) => res.json())
    .then((data) => {
      console.log(data);

      // Dispatch data to the respective handlers
      requestFeatured(data.featured);
      requestArrivals(data.newArrivals);
    })
    .catch((err) => console.log(err));
}

/**
 * Handle the display of featured products.
 */
function requestFeatured(products) {
  const featuredContainer = document.querySelector(".featured");
  const name = "Featured Products";
  catalogue(products, featuredContainer, name);
}

/**
 * Handle the display of new arrival products.
 */
function requestArrivals(products) {
  const arrivalsContainer = document.querySelector(".new_arrivals");
  const name = "New Arrivals";
  catalogue(products, arrivalsContainer, name);
}

/**
 * Fetch and display products by category when a category is selected.
 */
function requestProductsbyCategory(category) {
  const categoryList = document.querySelectorAll("nav li");
  categoryList.forEach((cat) => {
    if (cat.textContent === category) {
      cat.classList.add("border-cyan-50");
      cat.classList.remove("border-cyan-800");
    } else {
      cat.classList.remove("border-cyan-50");
      cat.classList.add("border-cyan-800");
    }
  });
  window.location.href = "#" + category; // Add category name to the URL
  fetch(`http://localhost:8081/user/backend/catalogue.php?category=${category}`)
    .then((res) => res.json())
    .then((data) => {
      console.log(data.categoryProducts);
      const main = document.querySelector("main");
      if (data.categoryProducts) {
        main.innerHTML = "";
      }
      const name = category;
      catalogue(data.categoryProducts, main, name);
    })
    .catch((err) => console.log(err));
}

/**
 * Populate the catalogue with products.
 */
function catalogue(products, container, name) {
  if (products) {
    // Create a flex container for the products
    const header = document.createElement("h1");
    header.className = "font-semibold text-5xl text-center mt-28  mb-24";
    header.textContent = name;
    container.appendChild(header);
    const div = document.createElement("div");
    div.className = "grid grid-cols-3 w-3/4 mx-auto gap-28";

    container.appendChild(div);

    // Iterate over each product
    products.forEach((element) => {
      // Create a container for each product with styling
      const prod = document.createElement("div");
      prod.addEventListener("click", () =>
        displayOverlay(productView.call(element))
      );
      prod.className =
        "bg-white col-span-1 shadow-md rounded-xl text-gray-700 duration-500 hover:scale-105 hover:shadow-xl";

      // Add the background image of the product
      const image = document.createElement("div");
      image.className = "bg-center bg-cover h-80 w-full mb-6";
      image.style.backgroundImage = `url('${element.image}')`;
      prod.appendChild(image);

      // Display the product name
      const name = document.createElement("div");
      name.className = "pb-3 font-semibold text-center text-xl";
      name.textContent = element.name;
      prod.appendChild(name);

      // Display the product price
      const price = document.createElement("div");
      price.className = "text-center pb-4";
      price.textContent = `$${element.price}`;
      prod.appendChild(price);

      // Add the product container to the flex container
      div.appendChild(prod);
    });
  }
}

function productView() {
  // Create the grid container
  const grid = document.createElement("div");
  grid.className = "grid grid-cols-2 gap-8";

  // Create the image container
  const imageContainer = document.createElement("div");
  imageContainer.className = "col-span-1 rounded-xl overflow-hidden";
  const image = document.createElement("img");
  image.src = this.image;
  image.alt = "Product Image";
  image.className = "w-full h-full object-cover rounded-lg";
  imageContainer.appendChild(image);

  // Create the details container
  const detailsContainer = document.createElement("div");
  detailsContainer.className = "col-span-1";

  // Add product name
  const name = document.createElement("h1");
  name.className = "font-semibold text-2xl my-2";
  name.textContent = this.name;
  detailsContainer.appendChild(name);

  // Add product price
  const price = document.createElement("p");
  price.className = "text-gray-500 text-xl font-semibold my-2";
  price.textContent = `$${this.price}`;
  detailsContainer.appendChild(price);

  // Add product description
  const description = document.createElement("p");
  description.className = "text-gray-500 mt-3 mb-16";
  description.textContent = this.description;
  detailsContainer.appendChild(description);

  // Add stock information
  const stockInfo = document.createElement("p");

  if (this.stock == 0) {
    stockInfo.className = "text-gray-500 mb-2";
    stockInfo.textContent = "Out of stock";
  } else if (this.stock < 5) {
    stockInfo.className = "text-red-500 mb-2";
    stockInfo.textContent = `Left in stock: ${this.stock}`;
  } else {
    stockInfo.style.display = "none";
  }

  detailsContainer.appendChild(stockInfo);

  // Add quantity with + - buttons
  const quantityContainer = document.createElement("div");
  quantityContainer.className = "flex mb-3";

  // Declare quantityText element
  let quantityText;

  if (this.stock == 0) {
    quantityContainer.style.display = "none";
  } else {
    // Create minus button
    const minusButton = document.createElement("button");
    minusButton.textContent = "-";
    minusButton.className = "bg-gray-200   py-1 px-2 rounded-l";
    minusButton.addEventListener("click", () => {
      const currentQuantity = parseInt(quantityText.textContent);
      if (currentQuantity > 1) {
        quantityText.textContent = currentQuantity - 1;
        // Update the cart and total here if needed
      }
    });

    // Create quantity text element
    quantityText = document.createElement("p");
    quantityText.id = "quantity";
    quantityText.textContent = 1; // default quantity
    quantityText.className =
      "w-8 border-2 border-gray-200  py-1 text-sm text-center m-0";

    // Create plus button
    const plusButton = document.createElement("button");
    plusButton.textContent = "+";
    plusButton.className = "bg-gray-200   py-1 px-2 rounded-r";
    plusButton.addEventListener("click", () => {
      const currentQuantity = parseInt(quantityText.textContent);
      if (currentQuantity < this.stock) {
        quantityText.textContent = currentQuantity + 1;
        // Update the cart and total here if needed
      }
    });

    quantityContainer.appendChild(minusButton);
    quantityContainer.appendChild(quantityText);
    quantityContainer.appendChild(plusButton);
  }

  detailsContainer.appendChild(quantityContainer);

  // Add to cart button
  const button = document.createElement("button");
  if (this.stock == 0) {
    button.disabled = true;
    button.textContent = "Out of stock";
    button.className = "bg-gray-500 text-white px-4 py-2 rounded";
  } else {
    button.className =
      "bg-cyan-800 text-white px-4 py-2 rounded hover:bg-cyan-600";
    button.addEventListener(
      "click",
      function () {
        addProductToCart.call(this); // Call addProductToCart with the correct context
      }.bind({
        id: this.id,
        name: this.name,
        price: this.price,
        image: this.image,
        stock: this.stock,
      })
    );
    button.addEventListener("click", () => {
      alert("Product added to cart");
    });

    button.textContent = "Add to cart";
  }
  detailsContainer.appendChild(button);

  // Append the containers
  grid.appendChild(imageContainer);
  grid.appendChild(detailsContainer);
  return grid;
}

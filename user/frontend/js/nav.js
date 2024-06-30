document.addEventListener("DOMContentLoaded", requestCategories);

function requestCategories() {
  fetch(`${domain}/user/backend/menu.php`)
    .then((res) => res.json())
    .then((data) => {
      // console.log(data);
      const categoryList = document.getElementById("nav"); // Assuming you want to append categories to an element with id "nav"

      if (data.categories) {
        data.categories.forEach((cat) => {
          const categoryItem = document.createElement("li"); // Create a new li element
          categoryItem.className =
            "nav-links cursor-pointer px-4 py-1 hover:border-cyan-50 transition ease-in-out duration-500  rounded border-cyan-800 border-2";
          categoryItem.textContent = cat; // Set text content to category name
          categoryItem.addEventListener(
            "click",
            requestProductsbyCategory.bind(null, cat)
          );
          categoryList.appendChild(categoryItem); // Append to categoryList
        });
      }
    })
    .catch((err) => console.log(err));
}

function getCategoryProducts() {
  console.log("hi");
}

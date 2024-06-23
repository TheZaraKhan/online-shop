// overlay.js

/**
 * Display an overlay with the given content.
 * @param {HTMLElement} overlayContent - The content to display inside the overlay.
 */
function displayOverlay(overlayContent) {
  let overlay = document.querySelector(".overlay");

  // If overlay already exists, update its content
  if (overlay) {
    const content = overlay.querySelector(".overlay-content");
    content.innerHTML = ""; // Clear existing content
    content.appendChild(overlayContent);
    return; // Exit function early if overlay already exists
  }

  // If overlay doesn't exist, create a new one
  overlay = document.createElement("div");
  overlay.className =
    "overlay fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50";

  // Disable page scrolling
  document.body.style.overflow = "hidden";

  // Create the content container
  const content = document.createElement("div");
  content.className =
    "overlay-content bg-white w-2/4 min-h-1/2 mx-auto p-10 rounded-xl shadow-xl relative";
  content.style.maxHeight = "75vh"; // Limit maximum height to 75% of viewport height
  content.style.overflowY = "auto"; // Enable vertical scrolling if content exceeds height

  // Create the close button
  const closeButton = document.createElement("div");
  closeButton.className = "cursor-pointer float-right -mt-4 -mr-4";
  closeButton.innerHTML = `<i class="fa fa-times" aria-hidden="true"></i>`;
  closeButton.addEventListener("click", () => {
    removeOverlay();
  });
  content.appendChild(closeButton);

  // Add event listener to close overlay when clicking outside the content
  overlay.addEventListener("click", (event) => {
    if (event.target === overlay) {
      removeOverlay();
    }
  });

  content.appendChild(overlayContent);
  overlay.appendChild(content);
  document.body.appendChild(overlay);
}

/**
 * Remove the currently displayed overlay.
 */
function removeOverlay() {
  const overlay = document.querySelector(".overlay");
  if (overlay) {
    overlay.parentElement.removeChild(overlay);
    document.body.style.overflow = ""; // Restore page scrolling
  }
}

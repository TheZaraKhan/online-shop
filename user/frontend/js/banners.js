document.addEventListener("DOMContentLoaded", requestBanners);

function requestBanners() {
  fetch("http://localhost:8081/user/backend/banner.php")
    .then((res) => res.json())
    .then((data) => {
      // console.log(data);
      if (data.banner) {
        const banners = data.banner;
        banners.forEach((banner) => {
          const slide = document.createElement("div");
          slide.className =
            "swiper-slide align-text-bottom bg-center justify-end";
          slide.style.backgroundImage = `url('http://localhost:8081/${banner.image}')`;
          slide.style.height = "50vh";
          const bannersection = document.querySelector(".swiper-wrapper");

          const content = document.createElement("div");
          content.className = "absolute bottom-1/4 left-0 right-0 text-center";

          const heading = document.createElement("h1");
          heading.append(banner.name);
          heading.className = "text-6xl text-bold my-3";

          const description = document.createElement("p");
          description.append(banner.description);
          description.className = "text-2xl my-3";

          const button = document.createElement("button");
          button.className =
            "mt-6  py-3 px-6 border rounded border-cyan-800 text-cyan-800 hover:bg-cyan-800 hover:text-white transition ease-in-out duration-400";
          button.append("Shop Now");

          bannersection.appendChild(slide);
          slide.appendChild(content);
          content.appendChild(heading);
          content.appendChild(description);
          content.appendChild(button);
        });
        // initializing swiper
        new Swiper(".swiper", {
          loop: true,
          speed: 1000,
          pagination: {
            el: ".swiper-pagination",
            clickable: true,
          },
          navigation: {
            nextEl: ".swiper-button-next",
            prevEl: ".swiper-button-prev",
          },
          effect: "slide",
          on: {
            init: function () {
              // Change the color of the navigation buttons
              document.querySelector(".swiper-button-next").style.color =
                "rgb(21 94 117)";
              document.querySelector(".swiper-button-prev").style.color =
                "rgb(21 94 117)";
              document.querySelector(
                ".swiper-pagination-bullet-active"
              ).style.backgroundColor = "rgb(21 94 117)";
            },
          },
          autoplay: {
            delay: 7000,
          },
        });
      }
    })
    .catch((err) => console.log(err));
}

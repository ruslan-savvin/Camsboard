//Savvin Ruslan 2024
export { lazyImagesStart };
const observer = new IntersectionObserver((entries, observer) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      const srcset = entry.target.dataset.srcset;
      const sizes = entry.target.dataset.sizes;
      entry.target.srcset = srcset;
      entry.target.sizes = sizes;
      observer.unobserve(entry.target);
    }
  });
});
const lazyImagesStart = (lazyImages) => {
  lazyImages.forEach((image) => {
    observer.observe(image);
  });
};

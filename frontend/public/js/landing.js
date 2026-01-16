// Parallax scroll effect for smoky bubbles
document.addEventListener('DOMContentLoaded', () => {
  const bubbles = document.querySelectorAll('.bubble');

  window.addEventListener('scroll', () => {
    const scrolled = window.pageYOffset;
    
    bubbles.forEach((bubble, index) => {
      const speed = 0.15 + (index * 0.05);
      const yPos = scrolled * speed;
      bubble.style.transform = `translateY(${yPos}px)`;
    });
  });
});



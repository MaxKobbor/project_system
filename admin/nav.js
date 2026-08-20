document.addEventListener("DOMContentLoaded", () => {
  const sidebar = document.querySelector(".sidebar");
  const collapseBtn = document.getElementById("collapseBtn");
  const logoutBtn = document.getElementById("logoutBtn");

  if (sidebar && collapseBtn) {
    collapseBtn.addEventListener("click", () => {
      sidebar.classList.toggle("collapsed");
    });
  }

  document.querySelectorAll(".nav button").forEach((btn) => {
    btn.addEventListener("click", () => {
      const list = btn.closest(".nav");
      list
        .querySelectorAll("button")
        .forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
    });
  });

  if (logoutBtn) {
    logoutBtn.addEventListener("click", (e) => {
      e.preventDefault();
      
      const fireLogout = () => {
        Swal.fire({
          title: 'ต้องการออกจากระบบใช่หรือไม่?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          cancelButtonColor: '#6c757d',
          confirmButtonText: 'ใช่, ออกจากระบบ',
          cancelButtonText: 'ยกเลิก',
          fontFamily: 'Sarabun'
        }).then((result) => {
          if (result.isConfirmed) {
            window.location.href = logoutBtn.tagName === 'A' && logoutBtn.hasAttribute('href') ? logoutBtn.getAttribute('href') : "config_a/logout.php?logout=1";
          }
        });
      };

      if (typeof Swal === 'undefined') {
        const script = document.createElement('script');
        script.src = "https://cdn.jsdelivr.net/npm/sweetalert2@11";
        script.onload = fireLogout;
        document.head.appendChild(script);
      } else {
        fireLogout();
      }
    });
  }

  if (window.lucide) {
    lucide.createIcons();
  }
  
  const mobileMenuBtn = document.getElementById("mobileMenuBtn");
  let mobileOverlay = document.querySelector(".mobile-overlay");
  
  if (mobileMenuBtn && sidebar) {
    if (!mobileOverlay) {
      mobileOverlay = document.createElement("div");
      mobileOverlay.className = "mobile-overlay";
      document.body.appendChild(mobileOverlay);
    }
    
    mobileMenuBtn.addEventListener("click", () => {
      sidebar.classList.add("open");
      mobileOverlay.classList.add("show");
    });
    
    mobileOverlay.addEventListener("click", () => {
      sidebar.classList.remove("open");
      mobileOverlay.classList.remove("show");
    });
  }
});

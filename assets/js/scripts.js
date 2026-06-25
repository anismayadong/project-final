document.addEventListener('DOMContentLoaded', function() {
    // สไลด์โชว์สำหรับเนื้อหาที่แนะนำ
    let currentSlide = 0;
    const heroImages = [
        '/api/placeholder/1200/400',
        '/api/placeholder/1200/400',
        '/api/placeholder/1200/400'
    ];
    
    const heroSection = document.querySelector('.hero-section');
    
    function changeHeroBackground() {
        currentSlide = (currentSlide + 1) % heroImages.length;
        heroSection.style.backgroundImage = `linear-gradient(rgba(26, 26, 46, 0.7), rgba(26, 26, 46, 0.7)), url('${heroImages[currentSlide]}')`;
    }
    
    // เปลี่ยนภาพพื้นหลังทุก 5 วินาที
    setInterval(changeHeroBackground, 5000);
    
    // การค้นหา
    const searchForm = document.querySelector('.search-bar');
    const searchInput = searchForm.querySelector('input');
    
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        if (searchInput.value.trim() !== '') {
            alert(`กำลังค้นหา: ${searchInput.value}`);
            // ในสถานการณ์จริงจะเปลี่ยนเป็นการนำทางไปยังหน้าผลการค้นหา
            // window.location.href = `search.html?q=${encodeURIComponent(searchInput.value)}`;
        }
    });
    
    
    
    // เอฟเฟกต์การเลื่อนแบบนุ่มนวล
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            
            if (targetId !== '#') {
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 100,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // แสดงการ์ดเพิ่มเติมเมื่อเลื่อนลงในหน้า
    function showMoreItems() {
        // ในสถานการณ์จริงจะเพิ่มรายการเนื้อหาเมื่อเลื่อนถึงตำแหน่งที่กำหนด
        const scrollPosition = window.scrollY + window.innerHeight;
        const pageHeight = document.body.offsetHeight;
        
        if (scrollPosition > pageHeight - 500) {
            // จำลองการโหลดข้อมูลเพิ่มเติม
            // loadMoreContent();
        }
    }
    
    // เพิ่มการตรวจจับการเลื่อนหน้าจอ
    window.addEventListener('scroll', showMoreItems);
    
    // ฟังก์ชั่นสำหรับการแสดงแจ้งเตือน
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // แสดงการแจ้งเตือน
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        // ซ่อนและลบการแจ้งเตือนหลังจาก 3 วินาที
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    
    // เพิ่ม CSS สำหรับการแจ้งเตือน
    const notificationStyle = document.createElement('style');
    notificationStyle.textContent = `
        .notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background-color: #333;
            color: white;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .notification.show {
            transform: translateY(0);
            opacity: 1;
        }
        
        .notification.success {
            background-color: #4CAF50;
        }
        
        .notification.error {
            background-color: #F44336;
        }
        
        .notification.info {
            background-color: #2196F3;
        }
    `;
    document.head.appendChild(notificationStyle);
    
    // ทดสอบการแจ้งเตือน (สามารถลบออกได้ในการใช้งานจริง)
    // setTimeout(() => {
    //     showNotification('ยินดีต้อนรับสู่ MangAnime Hub!', 'info');
    // }, 2000);
    
    // สร้างเอฟเฟกต์สวยงามเมื่อเลื่อนหน้าจอ
    function animateOnScroll() {
        const elements = document.querySelectorAll('.manga-card, .game-card, .section-title');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementBottom = element.getBoundingClientRect().bottom;
            const isVisible = (elementTop < window.innerHeight) && (elementBottom > 0);
            
            if (isVisible) {
                element.classList.add('visible');
            }
        });
    }
    
    // เพิ่ม CSS สำหรับเอฟเฟกต์การเลื่อน
    const scrollEffectStyle = document.createElement('style');
    scrollEffectStyle.textContent = `
        .manga-card, .game-card, .section-title {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .manga-card.visible, .game-card.visible, .section-title.visible {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(scrollEffectStyle);
    
    // เรียกใช้ฟังก์ชั่น animateOnScroll เมื่อโหลดหน้าและเมื่อเลื่อนหน้าจอ
    window.addEventListener('scroll', animateOnScroll);
    window.addEventListener('load', animateOnScroll);
    
    // สร้างระบบธีม (โหมดกลางวัน/กลางคืน)
    // สามารถเพิ่มปุ่มสลับธีมในส่วนนำทาง
    
    // สร้างฟังก์ชั่นสำหรับแสดงจำนวนการดูและคะแนนแบบเรียลไทม์
    function updateStats() {
        const statElements = document.querySelectorAll('.manga-stats span');
        
        statElements.forEach(element => {
            if (element.querySelector('i').classList.contains('fa-eye')) {
                // จำลองการเพิ่มจำนวนการดู
                const views = parseInt(element.textContent.replace(/[^0-9.]/g, ''));
                const randomIncrease = Math.floor(Math.random() * 10);
                if (Math.random() > 0.7) { // 30% โอกาสที่จะเพิ่มขึ้น
                    element.textContent = ` ${(views + randomIncrease / 10).toFixed(1)}K`;
                }
            }
        });
    }
    
    // อัปเดตสถิติทุก 10 วินาที
    setInterval(updateStats, 10000);
});
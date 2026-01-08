

        const sheetUrl = 'https://script.google.com/macros/s/AKfycbx3zrGCWuHjS-sAWVM7Mn0Xh8UHnklRRvArjbKlcNY-7FE1WqpQDhMU-TJq8JMVe_lTCQ/exec';

        function getSession() {
            const phone = localStorage.getItem('userPhone');
            if (!phone) {
                alert("رقم الهاتف غير موجود في التخزين المحلي.");
                return;
            }

            document.getElementById('loading').style.display = 'block';

            fetch(`${sheetUrl}?code=${phone}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';
                    if (data.error) {
                        window.location.href = "shadyelsharqawy/notsubs.html";
                    } else {
                        window.location.href = data.session;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('loading').style.display = 'none';
                    alert('حدث خطأ أثناء جلب بيانات الجلسة.');
                });
        }

         document.getElementById('exclusiveContentBtn').addEventListener('click', getSession);
        
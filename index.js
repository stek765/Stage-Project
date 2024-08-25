document.addEventListener('DOMContentLoaded', function () {
    // Navbar hiding/showing logic
    var navbar = document.querySelector('.topbar');
    var lastScrollTop = 0;
    var scrollThreshold = 100;

    window.addEventListener('scroll', function () {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        if (scrollTop > scrollThreshold) {
            if (scrollTop > lastScrollTop) {
                navbar.classList.add('hidden-navbar');
            } else {
                navbar.classList.remove('hidden-navbar');
            }
        }
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop;
    });

    // Show navbar on mouse movement near the top
    document.addEventListener('mousemove', function (event) {
        if (event.clientY < 50) {
            navbar.classList.remove('hidden-navbar');
        }
    });

    // Modal functionality for subscription
    var subscribeBtn = document.getElementById('subscribe-btn');
    var subscribeModal = new bootstrap.Modal(document.getElementById('subscribe-modal'));

    subscribeBtn.addEventListener('click', function (event) {
        event.preventDefault();
        subscribeModal.show();
    });


    // Form submission handling
    var subscribeForm = document.getElementById('subscribe-form');
    subscribeForm.addEventListener('submit', function (event) {
        event.preventDefault();
        var email = document.getElementById('email').value;
        alert('Grazie per esserti iscritto! Email: ' + email);
        subscribeModal.hide();
    });

    subscribeModal._element.addEventListener('hidden.bs.modal', function () {
        var modalBackdrops = document.querySelectorAll('.modal-backdrop');
        modalBackdrops.forEach(function (backdrop) {
            backdrop.remove();
        });
    });


    // Language selection functionality
    var languageSelector = document.getElementById('language-selector');
    languageSelector.addEventListener('change', function () {
        var selectedLang = this.value;
        changeLanguage(selectedLang);
    });

    // Load initial language translations
    var initialLang = languageSelector.value;
    changeLanguage(initialLang);

    // Function to fetch translations asynchronously
    async function fetchTranslations(lang) {
        try {
            const response = await fetch(`./static/translations_${lang}.json`);
            if (!response.ok) {
                throw new Error(`Failed to load ${lang} translations`);
            }
            return await response.json();
        } catch (error) {
            console.error(error);
        }
    }

    // Function to apply translations to elements
    function applyTranslations(translations) {
        document.querySelectorAll('[data-translate]').forEach(function (element) {
            var key = element.getAttribute('data-translate');
            if (translations[key]) {
                element.textContent = translations[key];
            }
        });
    }

    // Change language function
    async function changeLanguage(lang) {
        try {
            var translations = await fetchTranslations(lang);
            applyTranslations(translations);
        } catch (error) {
            console.error(error);
        }
    }

    // Loader hiding logic after a minimum delay
    window.addEventListener('load', function () {
        var minimumDelay = 1000;
        var pageLoadTime = new Date().getTime();
        var remainingTime = minimumDelay - (new Date().getTime() - pageLoadTime);

        function hideLoader() {
            document.getElementById('loader-container').style.display = 'none';
            document.getElementById('content').style.display = 'block';
        }

        if (remainingTime > 0) {
            setTimeout(hideLoader, remainingTime);
        } else {
            hideLoader();
        }
    });

    function getArrayEvents() {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: './connection/eventData.php',
                type: 'GET',
                data: {
                    action: 'get_allEvent'
                },
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.success && response.data != "") {
                        // Mappare i dati ricevuti per includere solo i campi desiderati
                        const events = response.data.map(event => ({
                            id: event.id,
                            lat: event.lat,
                            lon: event.lon,
                            name: event.nome_evento,
                            image: './IMG/art.jpg', // Imposta l'immagine predefinita
                            description: event.descrizione
                        }));
                        resolve(events);
                    } else {
                        console.log("ERROR");
                        resolve([]); // Risolve con un array vuoto in caso di errore
                    }
                },
                error: function () {
                    console.log("ERROR");
                    reject("Errore durante la richiesta AJAX");
                }
            });
        });
    }

    function initializeMap() {
        var map = L.map('map').setView([45.4400, 10.9930], 15.3);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Ottenere gli eventi e poi configurarli sulla mappa
        getArrayEvents().then(events => {
            events.forEach(function (event) {
                var customIcon = L.icon({
                    iconUrl: './IMG/redpin.png',
                    iconSize: [38, 50],
                    iconAnchor: [19, 50],
                    popupAnchor: [0, -50]
                });

                var marker = L.marker([event.lat, event.lon], { icon: customIcon }).addTo(map);

                var popupContent = document.createElement('div');
                popupContent.innerHTML = `
                    <h3>${event.name}</h3>
                    <img src="${event.image}" alt="Evento" style="width: 100%; max-width: 200px; margin-bottom: 10px;">
                    <p>${event.description}</p>
                    <button class="route-button">Ottieni Indicazioni</button>
                `;

                // Aggiungi l'event listener al pulsante dentro il popup
                popupContent.querySelector('.route-button').addEventListener('click', function () {
                    calculateRoute([event.lat, event.lon]);
                });

                marker.bindPopup(popupContent);
            });

            // Funzioni di calcolo della rotta e gestione della posizione
            var userPosition;
            var routingControl;

            function onLocationFound(e) {
                userPosition = e.latlng;

                L.marker(e.latlng).addTo(map)
                    .bindPopup("You are within " + e.accuracy + " meters from this point").openPopup();

                L.circle(e.latlng, e.accuracy).addTo(map);
            }

            function onLocationError(e) {
                alert(e.message);
            }

            map.on('locationfound', onLocationFound);
            map.on('locationerror', onLocationError);

            map.locate({ watch: true, setView: false, maxZoom: 16 });

            function calculateRoute(destination) {
                if (routingControl) {
                    map.removeControl(routingControl);
                }

                routingControl = L.Routing.control({
                    waypoints: [
                        L.latLng(userPosition),
                        L.latLng(destination)
                    ],
                    lineOptions: {
                        styles: [{ color: 'blue', opacity: 0.6, weight: 4 }]
                    },
                    routeWhileDragging: true,
                    show: false,
                    createMarker: function () { return null; }
                }).addTo(map);
            }

            // Event listener per il selettore di eventi
            var selectedEvent;
            document.getElementById('event-selector').addEventListener('change', function (e) {
                var selectedIndex = parseInt(e.target.value, 10); // Converti il valore in un numero intero
                if (!isNaN(selectedIndex)) {
                    selectedEvent = events.find(function (event) {
                        return event.id === selectedIndex;
                    });
                    //console.log(selectedIndex);  // Stampa l'indice selezionato
                    //console.log(events);          // Stampa l'array degli eventi
                    //console.log(selectedEvent);   // Stampa l'evento selezionato
                } else {
                    selectedEvent = null;
                }
            });

            // Event listener per il pulsante delle indicazioni
            document.getElementById('directions-btn').addEventListener('click', function () {
                if (selectedEvent) {
                    if (userPosition) {
                        calculateRoute([selectedEvent.lat, selectedEvent.lon]);
                    } else {
                        alert("Impossibile ottenere la posizione dell'utente.");
                    }
                } else {
                    alert("Seleziona un evento prima di ottenere le indicazioni.");
                }
            });
        }).catch(error => {
            console.error(error);
        });
    }
    // Function to initialize Leaflet map (funzione originale)
    /*function initializeMap() {
        var map = L.map('map').setView([45.4400, 10.9930], 15.3);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Event markers and routing setup
        var events = [
            { id: 1, lat: 45.4384, lon: 10.9916, name: 'Pasion Espaniola', image: './IMG/art.jpg', description: 'Una descrizione dell\'evento 1.' },
            { id: 2, lat: 45.4397, lon: 10.9924, name: 'Altrove LIVE', image: './IMG/sfondo.jpg', description: 'Una descrizione dell\'evento 2.' },
            { id: 3, lat: 45.4402, lon: 10.9932, name: 'Evento 3', image: './IMG/Univr.png', description: 'Una descrizione dell\'evento 3.' }
        ];

        events.forEach(function (event) {
            console.log("cao");
            var customIcon = L.icon({
                iconUrl: './IMG/redpin.png',
                iconSize: [38, 50],
                iconAnchor: [19, 50],
                popupAnchor: [0, -50]
            });
            console.log(event.id);
            var marker = L.marker([event.lat, event.lon], { icon: customIcon }).addTo(map);

            var popupContent = document.createElement('div');
            popupContent.innerHTML = `
                <h3>${event.name}</h3>
                <img src="${event.image}" alt="Evento" style="width: 100%; max-width: 200px; margin-bottom: 10px;">
                <p>${event.description}</p>
                <button class="route-button">Ottieni Indicazioni</button>
            `;

            // Aggiungi l'event listener al pulsante dentro il popup
            popupContent.querySelector('.route-button').addEventListener('click', function () {
                calculateRoute([event.lat, event.lon]);
            });

            marker.bindPopup(popupContent);
        });

        // Calculate route function
        var userPosition;
        var routingControl;

        function onLocationFound(e) {
            userPosition = e.latlng;


            L.marker(e.latlng).addTo(map)
                .bindPopup("You are within " + e.accuracy + " meters from this point").openPopup();

            L.circle(e.latlng, e.accuracy).addTo(map);
            userPositionSet = true;

        }

        function onLocationError(e) {
            alert(e.message);
        }

        map.on('locationfound', onLocationFound);
        map.on('locationerror', onLocationError);

        map.locate({ watch: true, setView: false, maxZoom: 16 });

        function calculateRoute(destination) {
            if (routingControl) {
                map.removeControl(routingControl);
            }

            routingControl = L.Routing.control({
                waypoints: [
                    L.latLng(userPosition),
                    L.latLng(destination)
                ],
                lineOptions: {
                    styles: [{ color: 'blue', opacity: 0.6, weight: 4 }]
                },
                routeWhileDragging: true,
                show: false,
                createMarker: function () { return null; }
            }).addTo(map);
        }

        // Event listener for event selector
        var selectedEvent;
        document.getElementById('event-selector').addEventListener('change', function (e) {
            var selectedIndex = e.target.value;
            if (selectedIndex !== "") {
                selectedEvent = events[selectedIndex];
            } else {
                selectedEvent = null;
            }
        });

        // Event listener for directions button
        document.getElementById('directions-btn').addEventListener('click', function () {
            if (selectedEvent) {
                if (userPosition) {
                    calculateRoute([selectedEvent.lat, selectedEvent.lon]);
                } else {
                    alert("Impossibile ottenere la posizione dell'utente.");
                }
            } else {
                alert("Seleziona un evento prima di ottenere le indicazioni.");
            }
        });
    }*/

    // Function to check if map container is visible
    function isMapVisible() {
        var mapContainer = document.getElementById('map');
        return mapContainer.offsetParent !== null; // Check if it has a parent that's visible
    }

    // Initialize map if visible on page load
    if (isMapVisible()) {
        initializeMap();
    } else {
        // Listen for visibility change and initialize map when visible
        var visibilityChangeObserver = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    initializeMap();
                    visibilityChangeObserver.disconnect(); // Disconnect observer once map is initialized
                }
            });
        });

        visibilityChangeObserver.observe(document.getElementById('map'));
    }
});

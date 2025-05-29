# Streamify - Streaming Content Platform

## Περιγραφή
Το Streamify είναι μια πλατφόρμα για τη διαχείριση και κοινοποίηση streaming περιεχομένου από το YouTube. Οι χρήστες μπορούν να δημιουργούν λίστες με τα αγαπημένα τους βίντεο, να ακολουθούν άλλους χρήστες και να ανακαλύπτουν νέο περιεχόμενο.

## Τεχνολογίες
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Backend:** PHP 8.1+
- **Database:** MySQL 8.0
- **Containerization:** Docker & Docker Compose
- **APIs:** YouTube Data API v3

## Εγκατάσταση & Εκτέλεση

### Προαπαιτούμενα
- Docker
- Docker Compose
- YouTube API Key (για αναζήτηση βίντεο)

### Βήματα Εγκατάστασης

1. **Κλωνοποίηση του repository:**
   ```bash
   git clone [repository-url]
   cd streamify
   ```

2. **Ρύθμιση YouTube API:**
   - Επισκεφθείτε το [Google Cloud Console](https://console.cloud.google.com/)
   - Δημιουργήστε νέο project ή επιλέξτε υπάρχον
   - Ενεργοποιήστε το YouTube Data API v3
   - Δημιουργήστε API credentials (API Key)
   - Επεξεργαστείτε το αρχείο `config.php` και προσθέστε το API key σας

3. **Εκκίνηση με Docker:**
   ```bash
   docker-compose up -d
   ```

4. **Πρόσβαση στην εφαρμογή:**
   - **Κύρια Εφαρμογή:** http://localhost:8080
   - **phpMyAdmin:** http://localhost:8081
   - **Database:** localhost:3306

### Προεπιλεγμένοι Χρήστες
- **Admin:** username: `admin`, password: `password`
- **Eleni:** username: `eleni`, password: `password`
- **Giannis:** username: `giannis`, password: `password`

## Δομή Αρχείων

```
streamify/
├── css/
│   └── style.css                 # Κύρια στυλ
├── js/
│   ├── theme.js                  # Διαχείριση θεμάτων
│   ├── accordion.js              # Λειτουργία accordion
│   └── form-validation.js        # Επικύρωση φορμών
├── database/
│   └── init.sql                  # Αρχικοποίηση βάσης δεδομένων
├── index.html                    # Αρχική σελίδα
├── about.html                    # Σελίδα σκοπού
├── help.html                     # Σελίδα βοήθειας
├── config.php                    # Ρυθμίσεις βάσης δεδομένων
├── register.php                  # Εγγραφή χρήστη
├── login.php                     # Σύνδεση χρήστη
├── logout.php                    # Αποσύνδεση
├── dashboard.php                 # Κεντρικός πίνακας
├── docker-compose.yml            # Docker configuration
└── README.md                     # Οδηγίες
```

**Ιόνιο Πανεπιστήμιο - Τμήμα Πληροφορικής - 2025**

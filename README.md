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

## Λειτουργίες

### Υλοποιημένες Λειτουργίες ✅
- ✅ Στατικές σελίδες (index, about, help) με accordion και theme switching
- ✅ Σύστημα εγγραφής και σύνδεσης χρηστών
- ✅ Light/Dark theme με cookie storage
- ✅ Responsive design
- ✅ Form validation (client & server-side)
- ✅ Dashboard με στατιστικά χρήστη
- ✅ Database schema και sample data
- ✅ Docker containerization

### Προς Υλοποίηση 🚧
- 🚧 Δημιουργία και διαχείριση λιστών
- 🚧 YouTube API integration για αναζήτηση βίντεο
- 🚧 Σύστημα follows/followers
- 🚧 Προβολή και αναπαραγωγή βίντεο
- 🚧 Αναζήτηση περιεχομένου
- 🚧 Εξαγωγή δεδομένων σε YAML
- 🚧 Session management και authentication pages
- 🚧 User profile management

## Ασφάλεια

- **Password Hashing:** Χρήση PHP password_hash() με bcrypt
- **SQL Injection Protection:** Prepared statements με PDO
- **XSS Protection:** htmlspecialchars() για όλα τα user inputs
- **Session Security:** Secure session configuration
- **Input Validation:** Client-side και server-side validation

## Βάση Δεδομένων

### Πίνακες:
- **users:** Στοιχεία χρηστών
- **content_lists:** Λίστες περιεχομένου
- **content_items:** Περιεχόμενο σε λίστες
- **user_follows:** Σχέσεις follows
- **search_logs:** Logs αναζητήσεων (προαιρετικό)

## YouTube API Setup

1. Επισκεφθείτε το [Google Cloud Console](https://console.cloud.google.com/)
2. Δημιουργήστε νέο project
3. Ενεργοποιήστε το YouTube Data API v3
4. Δημιουργήστε credentials (API Key)
5. Ενημερώστε το `config.php`:
   ```php
   define('YOUTUBE_API_KEY', 'your-api-key-here');
   ```

## Debugging & Development

### Logs
- **Apache Logs:** `docker-compose logs web`
- **MySQL Logs:** `docker-compose logs db`
- **PHP Errors:** Ελέγξτε τα Apache error logs

### Database Access
- **phpMyAdmin:** http://localhost:8081
- **Direct MySQL:** 
  ```bash
  docker exec -it streamify_db mysql -u root -p
  ```

## Παραδοτέα (Σύμφωνα με το PDF)

### Υποχρεωτικά ✅
1. ✅ Δύο στατικές σελίδες (about, help) με accordion και theme switching
2. ✅ Σελίδα εγγραφής με PHP validation και MySQL storage
3. 🚧 Σελίδες προβολής/επεξεργασίας προφίλ
4. 🚧 Διαχείριση λιστών περιεχομένου με YouTube integration
5. 🚧 Αναζήτηση περιεχομένου
6. 🚧 Εξαγωγή δεδομένων σε YAML
7. ✅ Authentication system με session management
8. ✅ Navigation menu με conditional visibility

### Bonus Features 🎯
- 🚧 Single Page Application implementation
- 🚧 Pagination για αποτελέσματα αναζήτησης
- ✅ Advanced form validation
- ✅ Responsive design

## Συμβουλές Ανάπτυξης

1. **Ασφάλεια:** Πάντα sanitize user inputs
2. **Performance:** Χρήση indexes στη βάση δεδομένων
3. **UX:** Progressive enhancement για JavaScript features
4. **Accessibility:** Semantic HTML και ARIA labels
5. **SEO:** Meta tags και structured data

## Troubleshooting

### Συχνά Προβλήματα:
- **Database Connection:** Ελέγξτε ότι ο MySQL container είναι running
- **Permissions:** Βεβαιωθείτε ότι τα αρχεία έχουν σωστά permissions
- **API Limits:** Το YouTube API έχει daily quotas
- **Session Issues:** Ελέγξτε τις session configurations

### Support
Για βοήθεια με το project, ελέγξτε:
- Documentation στον κώδικα
- Comments στα PHP αρχεία
- Error logs στα containers

---

**Δημιουργήθηκε για το μάθημα Τεχνολογίες Διαδικτύου**  
**Ιόνιο Πανεπιστήμιο - Τμήμα Πληροφορικής - 2025**
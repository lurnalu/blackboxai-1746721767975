# Cherish Orthopaedic Centre

A comprehensive orthopaedic healthcare platform with integrated e-commerce capabilities, telemedicine, and patient management system.

## Features

### For Patients
- 🏥 Online appointment scheduling with reminders
- 🎥 Telemedicine consultations via video calls
- 🛒 E-commerce platform for medical equipment
- 📚 Educational resources and rehabilitation guides
- 👤 Personal patient profiles
- 💳 Secure payments in KES (Kenyan Shillings)

### For Healthcare Providers
- 📊 Secure patient record management
- 📅 Appointment management system
- 🎯 Smart product recommendations
- 📱 Telemedicine integration

### Admin Features
- 🔐 Secure authentication system
- 🛍️ Shop management (products, prices in KES)
- 📖 Resource management
- 👨‍⚕️ Doctor profile management
- 📈 Analytics dashboard

## Technology Stack

### Frontend
- HTML5
- Tailwind CSS for styling
- JavaScript
- Google Fonts
- Font Awesome icons

### Backend
- Python Flask
- PostgreSQL database
- JWT authentication
- SQLAlchemy ORM

### Security Features
- Secure authentication
- Role-based access control
- Encrypted patient data
- Secure payment processing

## Getting Started

### Prerequisites
- Python 3.8+
- PostgreSQL
- Node.js (for development)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/your-username/cherish-orthopaedic-centre.git
cd cherish-orthopaedic-centre
```

2. Set up the backend:
```bash
cd backend
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate
pip install -r requirements.txt
```

3. Configure the database:
```bash
# Create .env file with your database credentials
cp .env.example .env
# Update .env with your PostgreSQL credentials
```

4. Initialize the database:
```bash
python init_db.py
```

5. Start the development server:
```bash
python app.py
```

6. Open the frontend:
```bash
cd ../frontend
# Use a local server to serve the frontend files
python -m http.server 8000
```

7. Visit `http://localhost:8000` in your browser

## Project Structure
```
cherish-orthopaedic-centre/
├── frontend/           # Frontend files
│   ├── index.html     # Landing page
│   ├── pages/         # Other HTML pages
│   ├── assets/        # Static assets
│   │   ├── css/      # Stylesheets
│   │   ├── js/       # JavaScript files
│   │   └── img/      # Images
├── backend/           # Backend application
│   ├── app.py        # Main application file
│   ├── models/       # Database models
│   ├── routes/       # API routes
│   └── utils/        # Utility functions
└── database/         # Database migrations and schema
```

## Contributing

Please read our contributing guidelines before submitting pull requests.

## Security

For security concerns, please email security@cherishortho.com

## License

All rights reserved. This project is proprietary and confidential.

## Contact

- Website: www.cherishortho.com
- Email: info@cherishortho.com
- Phone: +254 XXX XXX XXX

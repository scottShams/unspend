# unSpend - AI-Powered Expense Analysis & Wealth Blueprint

unSpend is a sophisticated web application that analyzes bank statements using AI to provide personalized financial insights and wealth-building strategies. The app uses OpenAI's GPT models to categorize expenses, identify spending leaks, and generate comprehensive wealth blueprints based on proven financial principles.

## 🚀 Features

### Core Functionality
- **AI-Powered Analysis**: Upload bank statements (PDF/CSV) for instant AI analysis
- **Smart Categorization**: Automatic expense categorization with leak detection
- **Wealth Blueprint**: Personalized financial action plans based on the 50/30/20 rule
- **Historical Tracking**: View analysis history and track financial progress
- **Email Verification**: Secure user authentication with email verification

### AI Features
- **OpenAI GPT Integration**: Advanced expense analysis and financial advice
- **Personalized Recommendations**: Custom action plans based on spending patterns
- **Behavioral Finance**: Psychology-based spending habit improvement strategies
- **Financial Health Scoring**: Comprehensive assessment of financial wellness

### Technical Features
- **Environment Configuration**: Secure credential management with .env files
- **Responsive Design**: Mobile-first UI with Tailwind CSS
- **PDF Export**: Download complete wealth blueprints as PDFs
- **Real-time Charts**: Interactive expense visualization with Chart.js

## 🛠️ Technology Stack

### Backend
- **PHP 8.0+**: Server-side logic and API handling
- **MySQL**: Database for user data and analysis storage
- **OpenAI API**: AI-powered financial analysis
- **PHPMailer**: Email verification system

### Frontend
- **HTML5/CSS3**: Semantic markup and styling
- **Tailwind CSS**: Utility-first CSS framework
- **JavaScript (ES6+)**: Interactive functionality
- **Chart.js**: Data visualization
- **jsPDF & html2canvas**: PDF generation

### Infrastructure
- **Composer**: PHP dependency management
- **Environment Variables**: Secure configuration management
- **PDO**: Secure database abstraction

## 📋 Prerequisites

- PHP 8.0 or higher
- MySQL 5.7 or higher
- Composer (PHP dependency manager)
- Node.js & npm (for frontend assets, optional)

## 🚀 Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/unspend.git
cd unspend
```

### 2. Install PHP Dependencies
```bash
composer install
```

### 3. Environment Configuration
```bash
# Copy the example environment file
cp .env.example .env

# Edit the .env file with your credentials
nano .env
```

### 4. Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE unSpend;
exit;

# Import database schema
mysql -u root -p unSpend < database/users.sql
mysql -u root -p unSpend < database/uploads.sql
```

### 5. Configure Environment Variables

Edit `.env` file with your actual credentials:

```env
# Database Configuration
DB_HOST=localhost
DB_NAME=unSpend
DB_USER=your_db_user
DB_PASS=your_db_password

# API Keys
OPENAI_API_KEY=sk-proj-your-openai-key-here
GEMINI_API_KEY=your-gemini-api-key

# Email Configuration
SMTP_HOST=sandbox.smtp.mailtrap.io
SMTP_USERNAME=your_smtp_username
SMTP_PASSWORD=your_smtp_password
SMTP_PORT=587
SMTP_ENCRYPTION=tls

# Application Settings
UPLOAD_DIR=uploads/
GEMINI_API_URL=https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-09-2025:generateContent
APP_URL=https://yourdomain.com
```

### 6. Web Server Configuration

#### Apache (with .htaccess)
Ensure `mod_rewrite` is enabled and place the project in your web root.

#### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /path/to/unspend;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 7. File Permissions
```bash
# Set proper permissions for uploads directory
chmod 755 uploads/
chmod 755 config/
chmod 644 .env
```

### 8. Access the Application
Open your browser and navigate to `http://yourdomain.com`

## 📖 Usage

### For Users
1. **Register**: Create an account with email verification
2. **Upload Statement**: Upload your bank statement (PDF or CSV)
3. **View Analysis**: See categorized expenses and spending insights
4. **Get Blueprint**: Unlock personalized wealth-building strategies
5. **Track Progress**: Monitor your financial health over time

### For Developers
1. **Code Structure**: Follow the MVC-like pattern with separate concerns
2. **API Integration**: Use the established OpenAI integration patterns
3. **Database**: Extend the existing schema for new features
4. **Security**: Always use prepared statements and validate inputs

## 🏗️ Project Structure

```
unspend/
├── config/                 # Configuration files
│   ├── config.php         # Application constants
│   ├── database.php       # Database connection
│   └── env.php           # Environment loader
├── database/              # Database schemas
│   ├── users.sql         # User table schema
│   └── uploads.sql       # Uploads table schema
├── functions/             # Business logic
│   ├── api_handler.php   # OpenAI API integration
│   ├── database_handler.php # Database operations
│   ├── email_sender.php  # Email functionality
│   ├── parsers.php       # File parsing logic
│   └── user_management.php # User operations
├── includes/              # Page components
│   ├── blueprint.php     # Wealth blueprint display
│   ├── summary_content.php # Analysis summary
│   └── *.php             # Other page includes
├── js/                    # JavaScript files
│   ├── summary.js        # Summary page logic
│   ├── blueprint.js      # Blueprint page logic
│   └── common.js         # Shared utilities
├── layouts/               # Page layouts
│   └── app.php           # Main application layout
├── .env                   # Environment variables (gitignored)
├── .env.example          # Environment template
├── composer.json         # PHP dependencies
├── index.php             # Main entry point
└── README.md            # This file
```

## 🔧 Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `DB_HOST` | Database host | `localhost` |
| `DB_NAME` | Database name | `unSpend` |
| `DB_USER` | Database username | `root` |
| `DB_PASS` | Database password | `` |
| `OPENAI_API_KEY` | OpenAI API key | Required |
| `GEMINI_API_KEY` | Google Gemini API key | Required |
| `SMTP_HOST` | SMTP server host | `sandbox.smtp.mailtrap.io` |
| `SMTP_USERNAME` | SMTP username | Required for email |
| `SMTP_PASSWORD` | SMTP password | Required for email |
| `APP_URL` | Application URL | `https://unspend.me` |

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines
- Follow PSR-12 coding standards
- Use meaningful commit messages
- Test your changes thoroughly
- Update documentation as needed

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support, email support@unspend.me or create an issue in the repository.

## 🙏 Acknowledgments

- **OpenAI**: For providing powerful AI models for financial analysis
- **Tailwind CSS**: For the beautiful, responsive UI framework
- **Chart.js**: For interactive data visualizations
- **PHPMailer**: For reliable email functionality

---

**Made with ❤️ for financial wellness and wealth building**
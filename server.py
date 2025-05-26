from flask import Flask, request, jsonify, render_template
from flask_sqlalchemy import SQLAlchemy
from flask_cors import CORS
from sqlalchemy import text
import os
import logging
from datetime import datetime

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialize Flask app
app = Flask(__name__)
CORS(app)


# Root route
@app.route("/")
def index():
    return jsonify(
        {
            "message": "Welcome to LabourLinks API",
            "endpoints": {
                "health": "/api/health",
                "signup": "/api/signup",
                "login": "/api/login",
                "jobs": "/api/jobs",
            },
            "status": "running",
            "timestamp": datetime.utcnow().isoformat(),
        }
    )


# Error handlers
@app.errorhandler(404)
def not_found(error):
    return (
        jsonify(
            {
                "error": "Not Found",
                "message": "The requested URL was not found on the server",
                "status_code": 404,
                "timestamp": datetime.utcnow().isoformat(),
            }
        ),
        404,
    )


@app.errorhandler(500)
def internal_error(error):
    return (
        jsonify(
            {
                "error": "Internal Server Error",
                "message": "An internal server error occurred",
                "status_code": 500,
                "timestamp": datetime.utcnow().isoformat(),
            }
        ),
        500,
    )


# Configure SQLite database
try:
    # Get the absolute path of the current directory
    base_dir = os.path.abspath(os.path.dirname(__file__))

    # Create instance directory if it doesn't exist
    instance_path = os.path.join(base_dir, "instance")
    if not os.path.exists(instance_path):
        os.makedirs(instance_path)
        logger.info(f"Created instance directory at {instance_path}")

    # Set up database path
    db_path = os.path.join(instance_path, "labourlinks.db")
    app.config["SQLALCHEMY_DATABASE_URI"] = f"sqlite:///{db_path}"
    app.config["SQLALCHEMY_TRACK_MODIFICATIONS"] = False

    # Initialize database
    db = SQLAlchemy(app)
    logger.info(f"Database initialized at {db_path}")

except Exception as e:
    logger.error(f"Error initializing database: {str(e)}")
    raise


# Models
class User(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    email = db.Column(db.String(120), unique=True, nullable=False)
    password = db.Column(db.String(80), nullable=False)
    user_type = db.Column(db.String(20), nullable=False)  # 'client' or 'worker'
    created_at = db.Column(db.DateTime, default=datetime.utcnow)

    def __repr__(self):
        return f"<User {self.email}>"


class Job(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    title = db.Column(db.String(100), nullable=False)
    description = db.Column(db.Text, nullable=False)
    location = db.Column(db.String(100), nullable=False)
    job_type = db.Column(db.String(50), nullable=False)
    salary = db.Column(db.String(50))
    posted_by = db.Column(db.Integer, db.ForeignKey("user.id"), nullable=False)
    created_at = db.Column(db.DateTime, default=datetime.utcnow)
    status = db.Column(db.String(20), default="open")

    def to_dict(self):
        return {
            "id": self.id,
            "title": self.title,
            "description": self.description,
            "location": self.location,
            "job_type": self.job_type,
            "salary": self.salary,
            "posted_by": self.posted_by,
            "created_at": self.created_at.isoformat(),
            "status": self.status,
        }


# Create tables
try:
    with app.app_context():
        db.create_all()
        logger.info("Database tables created successfully")
except Exception as e:
    logger.error(f"Error creating database tables: {str(e)}")
    raise


# API Endpoints
@app.route("/api/signup", methods=["POST"])
def signup():
    try:
        data = request.json
        if not data or not all(k in data for k in ["email", "password", "user_type"]):
            return jsonify({"error": "Missing required fields"}), 400

        user = User(
            email=data["email"],
            password=data["password"],  # In production, hash this!
            user_type=data["user_type"],
        )
        db.session.add(user)
        db.session.commit()
        logger.info(f"New user created: {data['email']}")
        return jsonify({"success": True, "message": "User created"}), 201
    except Exception as e:
        db.session.rollback()
        logger.error(f"Error in signup: {str(e)}")
        return jsonify({"error": str(e)}), 400


@app.route("/api/login", methods=["POST"])
def login():
    try:
        data = request.json
        if not data or not all(k in data for k in ["email", "password"]):
            return jsonify({"error": "Missing email or password"}), 400

        user = User.query.filter_by(email=data["email"]).first()
        if (
            user and user.password == data["password"]
        ):  # In production, use proper auth!
            logger.info(f"User logged in: {data['email']}")
            return jsonify({"success": True, "user_type": user.user_type})
        return jsonify({"error": "Invalid credentials"}), 401
    except Exception as e:
        logger.error(f"Error in login: {str(e)}")
        return jsonify({"error": str(e)}), 500


@app.route("/api/jobs", methods=["GET"])
def get_jobs():
    try:
        jobs = Job.query.filter_by(status="open").all()
        return jsonify([job.to_dict() for job in jobs])
    except Exception as e:
        logger.error(f"Error fetching jobs: {str(e)}")
        return jsonify({"error": str(e)}), 500


@app.route("/api/jobs", methods=["POST"])
def create_job():
    try:
        data = request.json
        if not data or not all(
            k in data
            for k in ["title", "description", "location", "job_type", "posted_by"]
        ):
            return jsonify({"error": "Missing required fields"}), 400

        job = Job(
            title=data["title"],
            description=data["description"],
            location=data["location"],
            job_type=data["job_type"],
            salary=data.get("salary"),
            posted_by=data["posted_by"],
        )
        db.session.add(job)
        db.session.commit()
        logger.info(f"New job created: {data['title']}")
        return jsonify({"success": True, "job": job.to_dict()}), 201
    except Exception as e:
        db.session.rollback()
        logger.error(f"Error creating job: {str(e)}")
        return jsonify({"error": str(e)}), 400


@app.route("/api/jobs/<int:job_id>", methods=["GET"])
def get_job(job_id):
    try:
        job = Job.query.get(job_id)
        if job:
            # Add more detailed job information
            job_data = job.to_dict()
            # Add additional fields that might be needed
            job_data.update(
                {
                    "company_name": "Sample Company",  # This should come from your database
                    "contact_name": "John Doe",  # This should come from your database
                    "contact_phone": "+254700000000",  # This should come from your database
                    "contact_email": "contact@example.com",  # This should come from your database
                    "responsibilities": "• Perform assigned tasks\n• Follow safety guidelines\n• Report to supervisor",
                    "requirements": "• Previous experience\n• Valid ID\n• Good communication skills",
                }
            )
            return jsonify(job_data)
        return jsonify({"error": "Job not found"}), 404
    except Exception as e:
        logger.error(f"Error fetching job {job_id}: {str(e)}")
        return jsonify({"error": str(e)}), 500


@app.route("/api/health", methods=["GET"])
def health_check():
    try:
        # Test database connection
        db.session.execute(text("SELECT 1"))
        return (
            jsonify(
                {
                    "status": "running",
                    "database": "connected",
                    "timestamp": datetime.utcnow().isoformat(),
                }
            ),
            200,
        )
    except Exception as e:
        logger.error(f"Health check failed: {str(e)}")
        return (
            jsonify(
                {
                    "status": "error",
                    "error": str(e),
                    "timestamp": datetime.utcnow().isoformat(),
                }
            ),
            500,
        )


if __name__ == "__main__":
    try:
        port = int(os.environ.get("PORT", 5000))
        logger.info(f"Starting server on port {port}")
        app.run(debug=True, port=port)
    except Exception as e:
        logger.error(f"Error starting server: {str(e)}")
        raise

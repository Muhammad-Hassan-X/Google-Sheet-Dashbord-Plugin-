#                   Client Dashboard System - WordPress Plugin

## About The Project

The Client Dashboard System is a custom WordPress plugin designed to integrate with Google Sheets to automate client management and provide a dedicated dashboard for clients. It allows businesses to manage client data, service statuses, and transaction history sourced from Google Sheets directly within their WordPress website.

Clients can log in using a unique ID to view their personalized dashboard, which displays their information, the current status of their services, and relevant transaction details. The system also supports automated email notifications to clients when their service statuses change.

**Key Features:**

* **Automated User Creation/Update:** Creates or updates WordPress users based on data from Google Sheets.
* **Client Dashboard:** A private, front-end dashboard for clients to view their information.
    * Displays personal details (name, email, phone, address).
    * Shows a list of services and their current statuses (e.g., "Request received," "In progress," "Missing docs," "Request denied," "Request completed," "Done").
    * Includes details for statuses like "Missing docs" or "Request denied."
    * Displays transaction history (currently based on property address).
    * Provides contact links for support.
* **ID-Based Client Login:** Simplifies client login using a unique ID from the Google Sheet.
* **Webhook Integration:** Uses Google Apps Script and a WordPress REST API webhook to receive data from Google Sheets.
* **Email Notifications:** Automatically notifies clients of changes to their service statuses.
* **Admin Management Area:**
    * Settings page to configure webhook details and other plugin options.
    * (Planned) A comprehensive interface for administrators to view users, filter them, and manually manage user data and service statuses.

## Built With

This project utilizes the following technologies:

* **WordPress:** Plugin development framework.
* **PHP:** Primary language for WordPress plugin development.
* **Google Apps Script (JavaScript):** For reading data from Google Sheets and sending it to the WordPress webhook.
* **HTML, CSS, JavaScript (jQuery):** For the plugin's admin interface and the client-facing dashboard.
* **WordPress REST API:** Used to create the webhook endpoint for receiving data from Google Sheets.

## Getting Started

To get this project up and running on your local server (e.g., XAMPP), follow these steps:

### Prerequisites

* **Local Server Environment:** XAMPP installed and running (or any other local server like MAMP, WAMP, Local by Flywheel).
* **WordPress Installation:** A fresh WordPress installation on your local server.
* **Google Account:** To create and manage Google Sheets and Google Apps Script.

### WordPress Plugin Installation

1.  **Download/Clone Plugin Files:**
    * Ensure you have all the plugin files organized in a folder (e.g., `client-dashboard-system`). The structure should be as outlined in the main plugin PHP file.
2.  **Place Plugin in WordPress:**
    * Navigate to your WordPress installation directory within your XAMPP `htdocs` folder (e.g., `C:\xampp\htdocs\your-wordpress-site\wp-content\plugins\`).
    * Copy the `client-dashboard-system` folder into this `plugins` directory.
3.  **Activate Plugin:**
    * Log in to your WordPress admin dashboard.
    * Go to **Plugins > Installed Plugins**.
    * Find "Client Dashboard System" in the list and click **Activate**.

### Google Sheets Setup

This is a crucial step to enable data flow from your Google Sheets to the WordPress plugin.

1.  **Prepare Your Google Sheet(s):**
    * Ensure your client data is in a Google Sheet.
    * Identify the columns that contain the necessary information for the plugin (e.g., Client ID, Name, Email, Phone, Address components, Service Names, Service Statuses).
    * **Important:** You will need to map your sheet's column headers (or column letters) to the specific field names the WordPress plugin expects (e.g., `ID`, `name`, `email`, `address_city`, `service_name_1`, `service_status_1`).
2.  **Create and Deploy Google Apps Script:**
    * For **each** Google Sheet you want to integrate, you need to create a Google Apps Script.
    * Open your Google Sheet, then go to **Extensions > Apps Script**.
    * **Write the Script:** The script will:
        * Read data from the relevant columns of your sheet (based on the mapping you define).
        * Format this data into a JSON payload. The keys in this JSON payload **must match** what the WordPress plugin expects (e.g., `ID`, `name`, `email`, `service_name_1`).
        * Include your **Webhook Secret Key** (see Plugin Configuration below) in the `headers` of the request for security (e.g., `X-Webhook-Secret: YOUR_SECRET_KEY`).
        * Send this JSON payload via an HTTP `POST` request to the WordPress plugin's webhook URL.
    * **Get Webhook URL:** You can find the webhook URL in your WordPress admin under **Client Dashboard > Settings** or on the main Client Dashboard admin page. For a local XAMPP setup, it will look like `http://localhost/your-wordpress-folder/wp-json/cds/v1/webhook`.
    * **Set Triggers:** In the Apps Script editor, set up a trigger for your script (e.g., `onEdit` or `onChange`) so it runs automatically when your sheet is updated.
    * **Authorize the Script:** You will need to authorize the script to access your spreadsheet data and connect to external services.
3.  **Example Snippet for Google Apps Script `payload` (customize based on your sheet):**
    ```javascript
    // Inside your Google Apps Script, when preparing data for one row
    const rowData = /* ... your logic to get data from a sheet row ... */ ;
    const sheetHeaders = /* ... your logic to get header names ... */ ;
    
    // --- THIS MAPPING IS WHAT YOU NEED TO DEFINE ---
    // Example: map your sheet's column header (or index) to plugin's expected field
    const columnMapping = {
      "Your Sheet Header for Client ID": "ID",
      "Your Sheet Header for Full Name": "name",
      "Your Sheet Header for Email": "email",
      "Your Sheet Header for Phone": "phone",
      "Your Sheet Header for City": "address_city",
      "Your Sheet Header for Street": "address_street",
      "Your Sheet Header for House No.": "address_number",
      "Your Sheet Header for Apt No.": "address_apt",
      "Your Sheet Header for Floor": "address_floor",
      "Your Sheet Header for Entrance": "address_entrance",
      "Your Sheet Header for Service 1 Name": "service_name_1",
      "Your Sheet Header for Service 1 Status": "service_status_1",
      "Your Sheet Header for Service 1 Missing Docs": "service_missing_docs_1",
      // ... add all other necessary mappings
    };

    let payload = {};
    sheetHeaders.forEach((header, index) => {
      if (columnMapping[header]) {
        payload[columnMapping[header]] = rowData[index];
      }
    });
    // --- END OF MAPPING LOGIC ---

    // Make sure payload contains all required fields like ID, name, email.
    // Send payload to WordPress...
    ```

### Plugin Configuration in WordPress Admin

1.  Navigate to **Client Dashboard > Settings** in your WordPress admin area.
2.  **Webhook Secret Key:** Enter a strong, unique secret key. This same key must be used in your Google Apps Script's request header for verification.
3.  **Number of Google Sheets:** Configure the number of sheets you plan to integrate (this mainly affects display in settings).
4.  **Sheet URLs (Optional for Webhook):** You can list your sheet URLs for reference.
5.  **Support Contact Details:** Configure the email and/or WhatsApp details that will appear on the client dashboard for support.
6.  Save settings.

## Usage

1.  **Data Flow:**
    * When data is added or updated in a configured Google Sheet, the Google Apps Script triggers.
    * The script sends the relevant row data to the WordPress plugin's webhook.
    * The plugin validates the request (using the secret key) and processes the data:
        * If a user with the provided `ID` (custom ID) or `email` exists, their WordPress profile and associated plugin data (address, services, etc.) are updated.
        * If no user exists, a new WordPress user is created, and their data is stored.
2.  **Client Login & Dashboard:**
    * Clients navigate to the standard WordPress login page (e.g., `http://localhost/your-wordpress-folder/wp-login.php`).
    * They use the "Client ID" field (provided from the Google Sheet) to log in.
    * Upon successful login, they are redirected to the "Client Dashboard" page (you need to create this page in WordPress and add the `[client_dashboard]` shortcode to it).
    * The dashboard displays their personal information, service statuses, and transaction history.
3.  **Email Notifications:**
    * If a service status for a client is updated via the Google Sheet, the client will receive an email notification about the change.
4.  **Admin Features:**
    * Admins can configure plugin settings.
    * (Future Development) Admins will be able to manage users and their service statuses directly from the WordPress backend via the "Manage Users & Services" page.

## Key Code Components (WordPress Plugin)

* `client-dashboard-system.php`: Main plugin file, initializes the plugin, loads dependencies, and registers hooks.
* `admin/class-cds-admin.php`: Handles admin menu creation, settings registration, and admin page displays.
* `includes/class-cds-google-sheets-handler.php`: Manages the webhook endpoint and initial processing of incoming data.
* `includes/class-cds-user-manager.php`: Core logic for creating/updating WordPress users and their custom meta data (address, services, transactions).
* `includes/class-cds-notifications.php`: Handles sending email notifications for status changes.
* `includes/class-cds-login-handler.php`: Implements the custom ID-based login functionality.
* `public/class-cds-public.php`: Handles the `[client_dashboard]` shortcode and enqueues front-end assets.
* `public/views/user-dashboard-template.php`: The HTML/PHP template for the client-facing dashboard.

## Current Status & Next Steps

This project provides a strong foundational WordPress plugin for the Client Dashboard System.

**Immediate Next Steps for Development/Setup:**

1.  **Finalize Google Sheet Column Mapping:** Define precisely which columns from your Google Sheet(s) map to the required plugin fields (e.g., `ID`, `name`, `email`, `address_city`, `service_name_1`, `service_status_1`).
2.  **Develop and Test Google Apps Script:** Create the Google Apps Script based on the finalized mapping to send data from your Google Sheet(s) to the WordPress webhook. This is critical for data flow.
3.  **Build Admin "Manage Users & Services" UI:** Develop the user interface in the WordPress admin area for administrators to view, filter, and manage client users and their service statuses.
4.  **Refine Service Data Handling:** Based on the complexity of your "services" (e.g., utility transfers with many specific fields), refine how this data is stored and displayed on the dashboard.
5.  **Thorough End-to-End Testing:** Test all aspects of the system, including data sync, user creation, login, dashboard display, and notifications.

## Contributing

Contributions, issues, and feature requests are welcome. Please feel free to fork the repository and submit pull requests.

## License

This plugin is licensed under the GPL v2 or later.
(See the `LICENSE.txt` file that is typically included with WordPress plugins, or state your chosen license).

---

Remember to replace placeholders like `http://localhost/your-wordpress-folder/` and `YOUR_SECRET_KEY` with actual values. You'll also want to be very specific in the Google Apps Script section once you've finalized your sheet mapping.

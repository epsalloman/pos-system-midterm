# TFA3 Validation and CRUD Learning Notes

## Draft Validation Rules

### Customer records

- Full name, email, and contact number are required.
- Email must have a valid format and must be unique.
- Contact number must contain digits only.
- Customer ID must be unique and cannot be edited.

### User records

- Username, email, password, and role are required.
- Username and email must be unique.
- Password must contain at least eight characters.
- User ID cannot be edited.
- Role must be selected from the allowed options.

### General rules

- Required fields cannot be blank.
- Duplicate records are not allowed.
- Invalid formats must be rejected.
- All create and update requests must be validated before saving.

## Corrected Validation Plan

The implemented TFA3 database contains customer `full_name`, `email`, and `phone` fields, plus user `username`, `full_name`, and `avatar` fields. Rules for email, password, and role on user records should be added only when those columns and features are introduced.

### Customers

- `full_name`: required, 2 to 100 characters. This prevents empty or implausibly short names and respects the database column size.
- `email`: required, valid email format, no more than 100 characters, and unique. On edit, the uniqueness check ignores the current customer's ID so an unchanged email remains valid.
- `phone`: optional in the current schema, but when supplied it must be an 11-digit Philippine mobile number beginning with `09`. A text field is used so a leading zero is preserved.
- `id`: never accepted from the form as editable data. The numeric ID comes from the route and is used only to find the record.

### Users

- `username`: required, 3 to 50 characters, letters and numbers only, and unique. On edit, the current user's ID is excluded from the uniqueness check.
- `full_name`: required, 2 to 100 characters.
- `avatar`: optional on edit. If supplied, it must be an actual image, use JPG/JPEG or PNG format, and be no larger than 2 MB. A random filename is generated, a 240 by 240 display image is prepared, and only the filename is stored.
- `id`: never accepted from the form as editable data; it is taken from the numeric route segment.

### General controls

- Validate again on the server for both create and update requests; browser attributes are only user-interface assistance.
- Use CSRF protection on POST forms.
- Escape values when displaying database or validation content.
- Keep database unique indexes as a final safeguard against duplicate email addresses and usernames.
- Return a 404 response when an edit or update ID does not exist.

## Short CRUD Workflow

### Add a customer

1. A GET request to `/customers/new` displays an empty form.
2. The user submits the form by POST to `/customers`.
3. The controller reads only the allowed fields and normalizes the email address.
4. CodeIgniter validates the full name, email, and optional phone number.
5. If validation fails, the user returns to the form with errors and their previous input.
6. If validation passes, the model inserts the record and the user is redirected to the customer list with a success message.

### Edit a customer

1. A GET request to `/customers/{id}/edit` finds the customer and pre-fills the form.
2. The user submits changes by POST to `/customers/{id}`.
3. The controller confirms the customer exists and validates the submitted fields.
4. The email uniqueness rule excludes the current ID.
5. If validation passes, the model updates the record and redirects to the customer list.

## Three Beginner Mistakes

### Mistake 1: Using the create uniqueness rule during edit

`is_unique[customers.email]` treats the current record's unchanged email as a duplicate. The edit rule must exclude the ID of the record being updated.

### Mistake 2: Trusting only HTML validation

Attributes such as `required`, `maxlength`, and `accept` can be bypassed. The controller must perform server-side validation before calling the model.

### Mistake 3: Updating every posted field

Passing all request data directly to the model can allow a user to change protected values such as an ID or stored filename. Build a small array containing only fields the form is allowed to change.

## CRUD and File Upload Risks

1. **Duplicate records caused by race conditions.** An application-level uniqueness check can pass for two requests at nearly the same time. A unique database index is still needed to enforce the rule.
2. **Malicious files disguised as images.** Checking only the filename extension is weak. Validate the uploaded file as an image, restrict both MIME type and extension, generate the stored name, and never use the user's original filename.
3. **Oversized files and resource exhaustion.** Large uploads can consume storage and memory during image processing. Enforce a 2 MB limit before preparing the image.
4. **Mass assignment or ID tampering.** Accepting the record ID or arbitrary posted fields can update the wrong data. Take the ID from a numeric route, verify the record exists, and allow only expected fields in the model.
5. **Stored cross-site scripting.** Names and usernames can contain unsafe text if output is printed directly. Escape values whenever records or validation input are rendered in HTML.
6. **Orphaned or overwritten avatar files.** Original filenames may collide and old images may remain after replacement. Generate a cryptographically random filename and remove the old avatar only after a successful database update.

## Submission Reminder

- Include screenshots of the complete ChatGPT conversation.
- Push the finished project and database export to the required GitHub repository.
- Test the hosted URL after deployment, including add, edit, invalid form submissions, duplicate values, avatar replacement, and the placeholder image.

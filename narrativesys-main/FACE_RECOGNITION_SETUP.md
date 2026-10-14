# Face recognition setup (face-api.js)

This project uses `face-api.js` in the existing Narrative Studios sign-in/register page.

## 1. Update the database

If you are installing the database from scratch, import `database/narrativestudios.sql` as usual; it now includes the `face_descriptor` column.

If you already have the database, open phpMyAdmin, select the SQL tab, and run the statement in `database/add_face_recognition.sql` once. Do not run the `ALTER TABLE` statement more than once.

## 2. Run locally

1. Put the project in your web server directory (for example, `htdocs/narrativesys-main`).
2. Confirm the database settings in `config/db.php` and that the database is named `narrativestudios`.
3. Open the site through `http://localhost/...`, not by opening the PHP file directly. Camera access generally works on `localhost` or HTTPS.
4. Use a current browser and allow camera permission. An internet connection is needed to load face-api.js and its model files from the CDN/model host.
5. Register a new account, open the camera, capture your face, check the privacy consent box, and create the account.
6. At sign-in, accounts with a stored face descriptor must provide both their password and a matching face scan. Older accounts without a face descriptor can still use password-only sign-in.

## Notes and limitations

- Face recognition is implemented as an additional check alongside the password, not as a replacement for the password.
- Existing accounts are not automatically enrolled. They can still sign in with their password; this version does not include a profile page to enroll their face later.
- The browser calculates a 128-number face descriptor; the server stores it in `users.face_descriptor` and compares it with a distance threshold of 0.60. Lighting, camera quality, angle, and the model threshold can affect matches. Test with your own devices before relying on it.
- Face descriptors are biometric data. Explain their purpose and retention to users, obtain appropriate informed consent, restrict database access, use HTTPS in deployment, and provide a process to remove biometric data. This sample uses an explicit consent statement but does not implement deletion or a formal privacy-management workflow.
- Camera access and model loading depend on browser permissions and external CDN/model availability. This is a prototype, not a certified biometric authentication system, and face matching can be spoofed by a photo/video.

# System Roles Overview

### 1.1 School Teacher

* **Role**: `school_teacher`
* **Base WP Role**: Custom role with elevated privileges
* **Purpose**: Teachers and coordinators who manage educational content and students

### 1.2 School Student

* **Role**: `student_school`
* **Base WP Role**: Custom role with student privileges
* **Purpose**: Students enrolled in the transportation education program

### 1.3 Private Student

* **Role**: `student_private`
* **Base WP Role**: Custom role with basic access
* **Purpose**: Independent students with self-paced learning access

---

## Overview Table – Flow Summary

| Flow # | Name                   | Creates User | Requires Payment | Activates Course   | Source of Access       |
| ------ | ---------------------- | ------------ | ---------------- | ------------------ | ---------------------- |
| 1      | Book Kit Purchase      | ❌            | ✅                | ❌ (via later code) | Woo product + checkout |
| 2      | Course Purchase        | ✅            | ✅                | ✅                  | Woo product + checkout |
| 3      | Promo Code Access      | ✅            | ❌                | ✅                  | Custom form + code     |
| 4      | Paid Subscription Only | ✅            | ✅                | ✅ (practice only)  | Woo product + checkout |

## Entry Forms Field Matrix

| Field                     | Flow 1: Book Kit | Flow 2: Course | Flow 3: Promo Code | Flow 4: Paid Sub Only |
| ------------------------- | ---------------- | -------------- | ------------------ | --------------------- |
| First Name                | ✅                | ✅              | ✅                  | ✅                     |
| Last Name                 | ✅                | ✅              | ✅                  | ✅                     |
| Mobile Number (+ Confirm) | ✅                | ✅              | ✅ *(username)*     | ✅ *(username)*        |
| Email                     | ✅                | ❌              | ❌                  | ❌                     |
| National ID (+ Confirm)   | ❌                | ✅ *(password)* | ✅ *(password)*     | ✅ *(password)*        |
| School Code               | ❌                | ✅ (optional)   | ✅ (optional)       | ✅ (optional)          |
| Class                     | ❌                | ✅ (if school)  | ✅ (if school)      | ❌                     |
| Promo Code                | ❌                | ✅ (optional)   | ✅                  | ✅ (optional)          |
| Program Selection         | ❌                | via product    | via code           | ✅ (manual)            |
| Subscription Duration     | ❌                | fixed          | fixed              | ✅ (selectable)        |
| Delivery Address          | ✅                | ❌              | ❌                  | ❌                     |
| Payment Required          | ✅                | ✅              | ❌                  | ✅                     |
| WP User Created           | ❌                | ✅              | ✅                  | ✅                     |

# Logical Entry Flows Overview

## User Flow Map

| Flow # | Entry Type             | Description                                                   |
| ------ | ---------------------- | ------------------------------------------------------------- |
| 1      | Book Kit Purchase      | Buy a physical book that includes a code to activate later    |
| 2      | Course Purchase        | Buy full access to online course + practice with registration |
| 3      | Promo Code Access      | Use a promo code to register and activate course              |
| 4      | Paid Subscription Only | Pay for practice access without a course (optional feature)   |

## Book Kit Purchase (Non-Logged-In User)

**LEGEND:** User Flow 1

**User Story:**
As a guest user, I want to purchase a book kit product, so that I can receive the book and later activate my learning access using a code.

**Acceptance Criteria:**

1. Guest user can access the product page and click "Buy Book Kit"
2. On click, user is redirected to WooCommerce checkout with a custom registration form
3. Form includes:

   * First Name
   * Last Name
   * Mobile Number
   * Email Address
   * Delivery method (Pickup / Delivery)
   * Delivery Address (City, Street, Mobile for delivery)
4. Payment options include credit card and Bit
5. Before payment confirmation, a message appears:
   "Note: You will only be able to register on the learning platform after receiving your kit. The code is inside the book."
6. Upon successful payment:

   * No WordPress user is created
   * System sends invoice to email
   * System logs order details for admin

**Technical Notes:**

* Must use custom WooCommerce checkout fields (no WP user creation at this stage)
* Form submission hooks into `woocommerce_checkout_create_order` to attach metadata
* Notification displayed using `woocommerce_review_order_before_submit` action
* Order metadata used later to track customer if needed

---

---

## Course Purchase Flow

**User Story:**
As a guest user, I want to purchase an online course and automatically get access to course content and practice, so that I can start learning immediately.

**LEGEND:** User Flow 2

**Acceptance Criteria:**

1. Guest user selects a course product (e.g. private car, electric bike, C1 truck)
2. User is redirected to a checkout form (or custom registration page)
3. The form includes:

   * First Name
   * Last Name
   * School Code (optional)
   * Class (if School Code provided)
   * Discount Code (optional)
   * Mobile Number + Confirmation
   * National ID + Confirmation (used as password)
   * Course selection is inferred from the product selected
4. User completes payment via Bit or credit card
5. Upon successful purchase:

   * WordPress user is created (if not existing)
   * User is logged in
   * User is enrolled to the selected LearnDash course and related practice content
   * Invoice is sent by email

**Technical Notes:**

* School Code field triggers lookup: show associated school, teacher, city, and class input
* Discount Code validation must support WooCommerce coupon rules
* Mobile and ID used for login credentials
* Auto-login after checkout via WooCommerce hooks or custom redirect with session
* Course enrollment done via LearnDash API or `ld_update_course_access()`

---

## Promo Code Access Flow

**LEGEND:** User Flow 3

**User Story:**
As a guest user, I want to register using a promo code from a book or teacher, so that I can activate access to a learning course without making a payment.

**Acceptance Criteria:**

1. Guest user accesses a public registration page for promo code activation
2. The form includes:

   * First Name
   * Last Name
   * Promo Code
   * School Code (optional, triggers school details lookup)
   * Mobile Number + Confirmation *(used as username)*
   * National ID + Confirmation *(used as password)*
3. On form submission:

   * Promo Code is validated:

     * If valid → create user, activate course
     * If already used → show existing user name
     * If invalid → show error and retry option
     * If empty → prompt user to either enter a code or go to paid registration (Flow 4)
4. User receives confirmation and is logged in
5. LearnDash access is activated to the correct course/group (based on code configuration)

**Technical Notes:**

* Form must be protected from multiple uses of the same promo code
* Codes stored in custom DB table or CPT with `used_by`, `used_at`, and `course_id`
* Mobile = `user_login`, National ID = `user_pass`
* Auto-login on success using `wp_signon()`
* Flow requires a dedicated page with shortcode or Elementor block

---

## Paid Subscription Only Flow

**LEGEND:** User Flow 4

**User Story:**
As a guest user, I want to purchase access to practice content without a full course, so that I can study on my own terms.

**Acceptance Criteria:**

1. Guest user selects the "Practice Only" product
2. User is redirected to checkout with registration form
3. The form includes:

   * First Name
   * Last Name
   * School Code (optional, triggers lookup)
   * Promo Code (optional)
   * Mobile Number + Confirmation *(used as username)*
   * National ID + Confirmation *(used as password)*
   * Practice Program selection (default: private car)
   * Subscription duration choice (2 weeks / 1 month / 2 months)
4. Before payment, user is prompted:

   * "If you already have a promo code, click here to redeem it instead."
5. Upon successful purchase:

   * WordPress user is created if not existing
   * User is logged in automatically
   * LearnDash practice group access is granted until June 30
   * Confirmation + invoice sent by email

**Technical Notes:**

* Similar checkout to course purchase, but without course access
* LearnDash enrollment can use dedicated group/category for practice-only users
* Promo Code logic reused from Flow 3, but optional
* System tracks expiration date for access independently
* Elementor-based CTA links should include flow identifier (`?flow=practice` or hidden input)

---

---

## Post-Purchase System Entry Behavior

**Applies to:** User Flows 2, 3, 4
**Excluded:** Flow 1 (Book Kit – deferred activation via code)

**User Story:**
As a newly registered user (via course purchase, promo code, or paid subscription), I want to be automatically logged in and directed to my member area with access to my program.

**Acceptance Criteria:**

1. After successful registration or payment:

   * User is logged in automatically
   * A welcome message appears: "Welcome! Your subscription is now active."
   * User is redirected to the main dashboard

2. On first login:

   * Dashboard shows:

     * Greeting: Hello \[first name]
     * Subscription valid until: \[date]
     * Days remaining: \[calculated]
     * Subjects completed: X / Remaining: Y
     * Current program name + link to change it

3. If user chooses to change program:

   * Warning is shown: "Switching program will erase all current practice progress."

4. User has ability to:

   * Enter additional promo codes at any time
   * If code valid → extend subscription by defined duration
   * If code used → message with existing user
   * If invalid → retry message

**Technical Notes:**

* Auto-login via `wp_signon()`
* Dashboard is a custom page or LearnDash group dashboard override
* Changing program must clear related usermeta or progress tables
* Promo code entry can be added via shortcode or Elementor section

---

## Distinction – Private Student vs. School Student

### Private Student (`student_private`)

* Access via: Free promo code (book), paid plan, or course purchase
* May choose learning program manually
* May switch programs (warning: data is erased)
* Can enter additional promo codes to extend subscription
* Subscription length: Variable (based on entry)

### School Student (`student_school`)

* Access via: school Excel import, promo code, or school coupon
* No ability to choose program (default = "חינוך תעבורתי")
* Cannot switch programs manually
* Promo code valid once only, must be entered at registration
* Subscription fixed: Valid until 30/6

### Activation Methods

| Method                     | Private Student | School Student |
| -------------------------- | --------------- | -------------- |
| Promo code entry           | ✅               | ✅              |
| Book purchase (with code)  | ✅               | ✅              |
| Paid checkout (no code)    | ✅               | ✅              |
| Excel import (admin only)  | ❌               | ✅              |
| Switch program post-login  | ✅               | ❌              |
| Extend subscription w/code | ✅               | ❌              |
| Auto-expire 30/6           | ❌ (varies)      | ✅              |

## In-System Learning Flows (Post Login)

**Context:** Applies to all users with an active subscription (Flows 2, 3, 4) after login. These flows define the user interaction inside their learning dashboard.

### Flow A – Main Navigation

**Trigger:** User lands on dashboard page
**Interface:** Four learning blocks appear:

* 📘 Learning by Topic (orange)
* 🧪 Topic Quizzes (pink)
* 🟩 Practice Exams (green)
* 🟣 Final Exams (purple)

---

### Flow B – Topic Learning (Orange Block)

**Flow:**

1. User clicks "Learn by Topic"
2. List of topics is displayed
3. Each topic includes:

   * Learning materials (text, images, video)
   * Sub-topics navigation
   * Practice questions for each sub-topic
4. At end of last sub-topic:

   * Button: "Go to next topic"

---

### Flow C – Topic Quiz (Pink Block)

**Flow:**

1. User clicks "Topic Quizzes"
2. Selects a topic → 30 random questions
3. Features:

   * "Hint" available once per incorrect attempt
   * Completion = summary page with score + hint usage breakdown
   * Re-entry resumes unfinished attempt

---

### Flow D – Practice Exams (Green Block)

**Flow:**

1. User clicks "Practice Exams"
2. Chooses between:

   * Auto-generated quiz (30 random questions)
   * Teacher-created quiz (if available)
3. Repeats as often as desired, each time new questions
4. Summary page after each attempt

---

### Flow E – Final Exams (Purple Block)

**Flow:**

1. User clicks "Final Exams"
2. Chooses:

   * Simulated theory test (30 random questions, 50 min)
   * Teacher exam (pre-configured question list)
3. No hints allowed
4. Results:

   * Numeric score (e.g., 85)
   * Pass/Fail (≤6 mistakes = pass)
   * Detailed error breakdown (question, image, answer, topic)

---

### Flow F – Progress Tracking

**Accessible From:** Dashboard or dedicated "Progress" tab
**Content Includes:**

* Topic quiz completion & hint stats
* Practice test history + hint usage
* Final exam results (avg, score breakdown, time spent)
* View full error history including question images

## System Logic Layers (User Creation, Purchase Info, Coupons, Status)

**Applies to:** Flows 2, 3, 4

### A. User Creation

* Created via WooCommerce checkout (Flow 2, 4) or custom promo form (Flow 3)
* Username = mobile number (validated for uniqueness)
* Password = national ID
* Stored as `student_private` role by default
* `user_registered` action can hook into external CRM or LMS

### B. Purchase Information Storage

* All WooCommerce orders include custom fields (school, program, etc.) via checkout
* Hook: `woocommerce_checkout_create_order_meta`
* Purchase triggers role assignment and LearnDash course/group enrollment (where applicable)

### C. Coupon / Promo Code Usage

* Coupons: WooCommerce native system with validation during checkout (Flow 2, 4)
* Promo Codes: Stored separately (custom DB or CPT) with fields: `code`, `course_id`, `used_by`, `expires_at`
* Hook: Custom validation on form submission (Flow 3)
* Code reuse shows friendly error
* Valid promo → triggers `ld_update_course_access()` and user creation if needed

### D. Subscription Status Tracking

* All users have expiration date set (meta: `subscription_valid_until`)
* Daily cron (or login hook) checks expiration and restricts access if expired
* Additional promo codes can extend subscription via date logic
* Admin override possible via usermeta editing

---

## System Connection Mapping (Screen/Flow → Logic/Hook/Action)

This section outlines how each key screen and user interaction maps to system logic, WordPress hooks, LearnDash functions, and data operations.

### 🔐 Authentication & Registration

| Step                    | Trigger Source | WP Hook / Function                      | Notes                                                  |
| ----------------------- | -------------- | --------------------------------------- | ------------------------------------------------------ |
| Register via checkout   | Flow 2, 4      | `woocommerce_checkout_update_user_meta` | Sets role, assigns `student_private`, stores ID, phone |
| Register via promo form | Flow 3         | `wp_insert_user` + `wp_signon()`        | Auto-login, program assignment                         |
| Promo code validation   | Flow 3         | Custom AJAX endpoint / REST             | Validates code and updates user meta                   |

### 💳 Purchase & Payment

| Step                       | Trigger Source          | WP Hook / Logic                          | Notes                                     |
| -------------------------- | ----------------------- | ---------------------------------------- | ----------------------------------------- |
| Save extra fields in order | Checkout (Flow 1, 2, 4) | `woocommerce_checkout_create_order_meta` | Saves school code, program, promo used    |
| Apply Woo coupon           | Flow 2, 4               | WooCommerce core                         | Discount in total                         |
| Store promo code           | Flow 3                  | Custom table / CPT                       | With status (used, user ID, course, date) |

### 🎓 Course & Group Enrollment

| Step                         | Flow   | LearnDash Method            | Notes                            |
| ---------------------------- | ------ | --------------------------- | -------------------------------- |
| Assign course after checkout | Flow 2 | `ld_update_course_access()` | Course inferred from Woo product |
| Assign practice group only   | Flow 4 | `ld_update_group_access()`  | Valid until June 30              |
| Assign via promo code        | Flow 3 | `ld_update_course_access()` | Based on promo-to-course config  |

### ⏳ Subscription Logic

| Step                      | Trigger Source           | Hook / Timing                    | Notes                                  |
| ------------------------- | ------------------------ | -------------------------------- | -------------------------------------- |
| Set subscription end date | Registration or payment  | `user_register`, order completed | `subscription_valid_until` meta        |
| Extend via promo code     | Logged-in user action    | Custom handler                   | Adds X days or sets fixed date         |
| Lock account on expiry    | Daily cron / login check | `init`, `wp_login`               | Removes course/group access if expired |

### 🧭 Dashboard Display & UX

| Display Element         | Data Source                 | Logic or Meta              | Notes                                   |
| ----------------------- | --------------------------- | -------------------------- | --------------------------------------- |
| Valid until + Days left | `subscription_valid_until`  | Date diff logic            | Format output on dashboard              |
| Completed topics        | LearnDash progress          | `ld_course_info`, usermeta | Quiz + lesson completion stats          |
| Promo code entry field  | Dashboard (shortcode block) | Custom AJAX                | Reuses Flow 3 validator                 |
| Program change warning  | Program switch action       | JavaScript + confirmation  | Erases past progress via metadata flush |

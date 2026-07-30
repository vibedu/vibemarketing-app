-- Razorpay payment links + the payments they produce.
-- Run once in phpMyAdmin with the app database selected. Safe to re-run.
CREATE TABLE IF NOT EXISTS payments (
  id            VARCHAR(64) PRIMARY KEY,      -- our id (also sent to Razorpay as a reference)
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  order_id      VARCHAR(64)  DEFAULT '',      -- campaign id in the app
  order_no      VARCHAR(64)  DEFAULT '',
  vendor_name   VARCHAR(190) DEFAULT '',
  amount        INT NOT NULL,                 -- rupees
  kind          VARCHAR(40)  DEFAULT 'Advance',
  status        VARCHAR(20)  DEFAULT 'created', -- created | paid | cancelled
  link_id       VARCHAR(64)  DEFAULT '',      -- Razorpay payment link id
  link_url      VARCHAR(255) DEFAULT '',      -- short URL to send the customer
  payment_id    VARCHAR(64)  DEFAULT '',      -- Razorpay payment id once paid
  method        VARCHAR(40)  DEFAULT '',      -- upi / card / netbanking ...
  paid_at       DATETIME NULL,
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_link (link_id),
  INDEX idx_payments_status (status)
);

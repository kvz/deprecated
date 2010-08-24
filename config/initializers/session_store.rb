# Be sure to restart your server when you modify this file.

# Your secret key for verifying cookie session data integrity.
# If you change this key, all old sessions will become invalid!
# Make sure the secret is at least 30 characters and all random, 
# no regular words or you'll be exposed to dictionary attacks.
ActionController::Base.session = {
  :key         => '_ruby.ormify.com_session',
  :secret      => 'dd9f12b36938470deda5109c8bc7a3748a0e0f90b88c873724fd45bbe4854262947c7d0c2df6de05b5c6fa24c9c4028914837f8fff48dbfc3a5790513bd8cf67'
}

# Use the database for sessions instead of the cookie-based default,
# which shouldn't be used to store highly confidential information
# (create the session table with "rake db:sessions:create")
# ActionController::Base.session_store = :active_record_store

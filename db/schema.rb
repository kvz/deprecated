# This file is auto-generated from the current state of the database. Instead of editing this file, 
# please use the migrations feature of Active Record to incrementally modify your database, and
# then regenerate this schema definition.
#
# Note that this schema.rb definition is the authoritative source for your database schema. If you need
# to create the application database on another system, you should be using db:schema:load, not running
# all the migrations from scratch. The latter is a flawed and unsustainable approach (the more migrations
# you'll amass, the slower it'll run and the greater likelihood for issues).
#
# It's strongly recommended to check this file into your version control system.

ActiveRecord::Schema.define(:version => 20100829185428) do

  create_table "orms", :primary_key => "uuid", :force => true do |t|
    t.integer  "type_id"
    t.text     "source"
    t.string   "file"
    t.string   "url"
    t.string   "ip"
    t.datetime "created_at"
    t.datetime "updated_at"
  end

  create_table "payments", :force => true do |t|
    t.string   "orm_id"
    t.string   "name"
    t.string   "company"
    t.string   "address1"
    t.string   "address2"
    t.string   "city"
    t.string   "state"
    t.string   "country"
    t.string   "zip"
    t.string   "ip"
    t.string   "payer_id"
    t.string   "token"
    t.float    "price"
    t.datetime "created_at"
    t.datetime "updated_at"
  end

  create_table "types", :force => true do |t|
    t.string   "name"
    t.datetime "created_at"
    t.datetime "updated_at"
  end

end

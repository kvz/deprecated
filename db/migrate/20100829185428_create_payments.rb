class CreatePayments < ActiveRecord::Migration
  def self.up
    create_table :payments do |t|
      t.string :orm_id
      t.string :name
      t.string :company
      t.string :address1
      t.string :address2
      t.string :city
      t.string :state
      t.string :country
      t.string :zip
      t.string :ip
      t.string :payer_id
      t.string :token
      t.float :price

      t.timestamps
    end
  end

  def self.down
    drop_table :payments
  end
end

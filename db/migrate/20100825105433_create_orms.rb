class CreateOrms < ActiveRecord::Migration
  def self.up
    create_table :orms, :id => false do |t|
      t.string :uuid, :limit => 36, :primary => true
      t.string :ip
      t.text :source
      t.boolean :payed
      t.string :file
      t.string :url

      t.timestamps
    end
  end

  def self.down
    drop_table :orms
  end
end

class AddTypeToOrm < ActiveRecord::Migration
  def self.up
    add_column :orms, :type_id, :integer
  end

  def self.down
    remove_column :orms, :type_id
  end
end

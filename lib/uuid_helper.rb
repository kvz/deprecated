require 'rubygems'
require 'uuidtools'
module UUIDHelper
  def before_create()
    self.uuid = UUIDTools::UUID.timestamp_create().to_s
  end
end

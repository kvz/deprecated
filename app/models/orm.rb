class Orm < ActiveRecord::Base
  set_primary_key "uuid"
  belongs_to :type
  include UUIDHelper
end

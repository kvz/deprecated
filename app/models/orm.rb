class Orm < ActiveRecord::Base
  set_primary_key "uuid"
  belongs_to :type
  validates_presence_of :source
  include UUIDHelper
end

class Orm < ActiveRecord::Base
  set_primary_key "uuid"
  belongs_to :type
  has_many :payments
  validates_presence_of :source
  include UUIDHelper
  
  def price
    200
  end

  def payed
    # @todo: Remove return false when done testing
    return false
    payments.count(:conditions => ["status = ? AND billing_mode != ?" , 'ok', ':test'])
  end

  def parse_source
    typePatterns = {
      :cakephp => {
        :defin => /^\s*([Cc]lass\s+([A-Z][A-Za-z0-9_]+)\s+[Ee][Xx][Tt][Ee][Nn][Dd][Ss]\s+[A-Z][A-Za-z0-9_]+Model)\s*\{(.+)\}/sm,
        :child => /(belongsTo)\s+=\s+(array\s*\([^;]+)/,
        :paren => /(hasMany|hasOne|hasAndBelongsToMany)\s+=\s+(array\s*\(>[^;]+)/,
      }
    }
    
    parsed = {}
    connections = {}
    connected = {}

    # Split source into models per type
    typeModels = {}
    typePatterns.each do |type, patterns|
      result = source.scan(patterns[:defin])
      if result.size then
        typeModels[type] = result
      end
    end
    # Order languages by modelcount
    typeModels.sort_by {|models| models.size}

    # Process each model's hasbtm properties
    typeModels.each do |type, models|
      models.each do |modelcol|
        modelDef, modelName, modelSource = modelcol
        parsed[modelName] = {:child => {}, :paren => {}}
        # child to
        if source = modelSource.match(typePatterns[type][:child])
          parsed[modelName][:child] = array_to_ruby type, source[2]
        end
        # parent of
        if source = modelSource.match(typePatterns[type][:paren])
          parsed[modelName][:paren] = array_to_ruby type, source[2]
        end
      end
    end
    
    # Make connections
    parsed.each do |modelName, modelRelations|
      connections = connected = {:modelName => {}}
      modelRelations.each do |relationType, relations|
        relations.each do |relationName, relationData|
          
        end
      end
    end


   return parsed.inspect

  end

  def array_to_ruby(type, str)
    case type
    when :cakephp
      phpCode = "<?php echo json_encode(" + str + ");"
      phpFile = '/tmp/' + self.uuid;
      tmpFile = File.new(phpFile, 'w')
      tmpFile.write(phpCode)
      tmpFile.close
      
      require 'open3'
      require 'json'
      cmd = "php " + phpFile
      stdin, stdout, stderr = Open3.popen3(cmd)
      
      obj = JSON.parse(stdout.readlines.join)
      return obj
      obj.each do|name, relationData|
        if !relationData.responds_to?(each)
          
        end
        if !relationData[:relationName]
          relationData[:relationName] = name
        end
      end
    end
  end

  def build_graph()
    if (!self.uuid)
      before_create
    end
    # http://github.com/glejeune/Ruby-Graphviz/blob/master/examples/sample01.rb
    # http://www.omninerd.com/articles/Automating_Data_Visualization_with_Ruby_and_Graphviz
    
    if !self.payed
      ext = 'png'
      watermark = 'Preview. Buy full version at http://ormify.com/' + self.uuid
    else
      ext = 'svg' 
    end

    url = '/graphs/' + self.uuid + '.' + ext
    file = File.join(RAILS_ROOT, 'public') + url

    require 'graphviz'

    # Create a new graph
    @parsed = self.parse_source
    
    
    g = GraphViz.new( :G, :type => :digraph )

    # Create two nodes
    hello = g.add_node( "Hello" )
    world = g.add_node( "World" )

    # Create an edge between the two nodes
    g.add_edge( hello, world )

    # Generate output image
    #g.output( :png => "hello_world.png" )
    g.output( "output" => ext, :file => file)
    
    self.file = file
    self.url = url
    self.save!
  end
end

class Orm < ActiveRecord::Base
  set_primary_key "uuid"
  belongs_to :framework
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
    frameworkPatterns = {
      :rails => {
        :defin => /^\s*(class\s+([A-Z][A-Za-z0-9_]+)\s+<\s+ActiveRecord::Base)\s+(.+?)end/sm,
        :child => /^(\s*)(belongs_to)\s+(.*)/sm,
        :paren => /^\s*(public|var)\s+\$(hasMany|hasOne|hasAndBelongsToMany)\s+=\s+(array\s*\([^;]+);/sm,
      },
      :cakephp => {
        :defin => /^\s*([Cc]lass\s+([A-Z][A-Za-z0-9_]+)\s+[Ee][Xx][Tt][Ee][Nn][Dd][Ss]\s+[A-Z][A-Za-z0-9_]+Model)\s*\{(.+?)\}/sm,
        :child => /^\s*(public|var)\s+\$(belongsTo)\s+=\s+(array\s*\([^;]+);/sm,
        :paren => /^\s*(public|var)\s+\$(hasMany|hasOne|hasAndBelongsToMany)\s+=\s+(array\s*\([^;]+);/sm,
      }
    }

    # Split source into models per framework
    frameworkModels = {}
    frameworkPatterns.each do |framework, patterns|
      result = source.scan(patterns[:defin])
      if result then
        frameworkModels[framework] = result
      end
    end
    # Order languages by modelcount
    frameworkModels.sort_by {|models| models.size}

    # Process each model's hasbtm properties
    parsed = {}
    frameworkModels.each do |framework, models|
      models.each do |modelcol|
        modelDef, modelName, modelSource = modelcol
        parsed[modelName] = {:child => {}, :paren => {}}
        # child to
        if source = modelSource.match(frameworkPatterns[framework][:child])
          parsed[modelName][:child] = array_to_ruby framework, source[3]
        end
        # parent of
        if source = modelSource.match(frameworkPatterns[framework][:paren])
          parsed[modelName][:paren] = array_to_ruby framework, source[3]
        end
      end
    end

    if parsed.size == 0 then
      raise "No models recognized in source"
    end

    # Make connections
    # which I knew how to make this compact without raising errors.
    connections = {}
    connected = {}
    parsed.each do |modelName, modelRelations|
      modelRelations.each do |relationType, relations|
        relations.each do |relationName, relationData|
          relationName = relationData[:relationName]

          if relationType == :child
            if !connections.has_key? modelName
              connections[modelName] = {}
            end
            if !connections[modelName].has_key? relationName
              connections[modelName][relationName] = {}
            end
            connections[modelName][relationName][relationType] = relationName
          else
            if !connections.has_key? relationName
              connections[relationName] = {}
            end
            if !connections[relationName].has_key? modelName
              connections[relationName][modelName] = {}
            end
            connections[relationName][modelName][relationType] = relationName
          end

          if !connected.has_key? modelName
            connected[modelName] = 0
          end
          connected[modelName] += 1
          if !connected.has_key? relationName
            connected[relationName] = 0
          end
          connected[relationName] += 1
        end
      end
    end

    return :models => parsed, :connected => connected, :connections => connections, :frameworkModels => frameworkModels
  end

  def array_to_ruby(framework, str)
    case framework
    when :cakephp
      # Let PHP Save the array code as json
      phpCode = "<?php echo json_encode(" + str + ");"
      phpFile = '/tmp/' + self.uuid;
      tmpFile = File.new(phpFile, 'w')
      tmpFile.write(phpCode)
      tmpFile.close

      # Let Ruby parse the json so we have an actual enumerable
      require 'open3'
      require 'json'
      cmd = "php " + phpFile
      stdin, stdout, stderr = Open3.popen3(cmd)
      obj = JSON.parse(stdout.readlines.join)

      # Consistently format relationData
      # and add relationName & className
      @prop = {}
      obj.each do|name, data|
        # Nest if string
        if data.kind_of?(String)
          name = data
        end
        if !data.kind_of?(Hash)
          data = {}
        end
        if !name.kind_of?(String)
          raise str + ' results in non-string name: ' + name.inspect
        end

        @prop[name] = data
        if !@prop.has_key? :relationName
          @prop[name][:relationName] = name
        end
        if !@prop.has_key? :className
          @prop[name][:className] = name
        end
      end

      return @prop
    end
    raise "Unsupported framework: " + framework
  end

  def build_graph()
    if (!self.uuid)
      before_create
    end
    # http://github.com/glejeune/Ruby-Graphviz/blob/master/examples/sample01.rb
    # http://www.omninerd.com/articles/Automating_Data_Visualization_with_Ruby_and_Graphviz

    if !self.payed
      exts = ['png']
      watermark = 'Preview. Buy full version at http://ormify.com/' + self.uuid
    else
      exts = ['svg']
    end

    edgeOpts = {:arrowsize => 0.6, :weight => 1, :fontsize => 11, :fontname => 'arial', :fontcolor => 'gray45', :color => 'gray77'}
    graphOpts = {:normalize => true, :ratio => 0.3, :ranksep => 0.9, :nodesep => 0.1, :rankdir => 'BT'}
    nodeOpts = {
      :fontsize => 12,
      :fontname => 'arial',
      :color => '1 1 1',
      :shape => 'square',
      :height => 0.8
    }

    # Initialize Data
    definitions = self.parse_source
    if definitions.is_a?(String) then
      return definitions
    end

    url = '/graphs/' + self.uuid + '.' + 'EXT'
    file = File.join(RAILS_ROOT, 'public') + url


    # Create a new graph
    require 'graphviz'
    g = GraphViz.new :G, graphOpts

    # Add nodes
    definitions[:models].each do|modelName, relations|
      g.add_node modelName, nodeOpts.merge(:label => modelName)
    end

    # Add edges
    definitions[:connections].each do|from, tos|
      tos.each do|to, frameworks|
        dirs = {:back => false, :forward => false}
        edgeOpts[:label] = ''

        if frameworks.count == 2 then
          edgeOpts[:dir] = 'both'
        end

        frameworks.each do|framework, name|
          if framework == :child && to != name
              edgeOpts[:label] = name
          end
          if framework == :paren && from != name
              edgeOpts[:label] = name
          end
        end
        g.add_edge from, to, edgeOpts
      end
    end

    # Create an edge between the two nodes
    #g.add_edge( hello, world )

    # Generate output images
    exts.each do |ext|
      g.output( "output" => ext, :file => file.gsub('EXT', ext))
    end

    self.file = file.gsub('EXT', exts.first)
    self.url = url.gsub('EXT', exts.first)
    self.save!
  end
end

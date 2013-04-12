#!/usr/bin/env ruby
require 'bundler'
Bundler.setup

require 'xcoder'
require 'versionomy'
require 'trollop'
require 'tempfile'
require 'shellwords'



def find_identity
	keychain = Xcode::Keychain.login
	keychain.identities.each { |id|
		if id.include? "iPhone Developer"
			return id
		end
	}
end

def find_profile
	Xcode::ProvisioningProfile.installed_profiles.each { |profile|
		if profile.name.include? "iOS Team"
			return profile.path
		end
	}
	return ""
end

default_project = Dir.glob('*.xcodeproj').first
opts = Trollop::options do
  opt :id, "Common-name of developer identity to use from keychain", :type => :string, :default => find_identity
  opt :id_file, "Path to developer identity (exported p12 file) (default: use login keychain)", :type => :string
  opt :id_password, "Password for developer identity file", :type => :string
  opt :keychain, "Keychain to use, not used when --id-file is given", :type => :string, :default => Xcode::Keychain.login.path
  opt :keychain_password, "Password for keychain", :type => :string
  opt :profile, "Path to provisioning profile to embed", :type => :string, :default => find_profile
  opt :xcodeproj, "Name of the project file", :type => :string, :default => default_project
  opt :target, "Target to build", :type => :string, :default => Xcode.project(default_project).targets.first.name
  opt :release, "Use Release-build configuration", :default => false
  opt :no_package, "Do not create IPA and dSYM files", :default => false
end

if opts[:release]
	build_config = :Release
else
	build_config = :Debug
end

# new keychain
if opts[:id_file]
	keychain = Xcode::Keychain.temp
	keychain.import opts[:id_file], opts[:id_password]
else
	keychain = Xcode::Keychain.new(opts[:keychain])
	if opts[:keychain_password]
		keychain.unlock(opts[:keychain_password])
		at_exit do
			keychain.lock
		end
	end
end

# load project
project = Xcode.project opts[:xcodeproj]
config = project.target(opts[:target]).config(build_config)

# increment build number
config.info_plist do |info|
	@version = Versionomy.parse(info.version)
	if @version.release_type == :beta
		@version = @version.bump(:beta_version)
		info.version = @version.to_s
	end
	info.marketing_version = info.version
	info.save
end

# setup builder
builder          = config.builder
builder.keychain = keychain
builder.profile = opts[:profile]

# identity
builder.identity = opts[:id]
builder.objroot  = 'build'

# build
builder.build

# package
if not opts[:no_package]
	builder.package
	puts "--ipa '#{builder.ipa_path}' --dsym '#{builder.dsym_zip_path}' --version '#{@version.to_s}'"
end

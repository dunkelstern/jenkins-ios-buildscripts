#!/usr/bin/env ruby
puts '<ul>'

log_garbage = true
File.readlines('changelog.xml').each { |line|
	next if line.strip.length == 0
	if line.match(/^\s+.*$/)
		line.gsub!('<', '&lt;')
		line.gsub!('>', '&gt;')
		if log_garbage
			puts '<li>' + line
			log_garbage = false
		else
			puts line
		end
	else
		if not log_garbage
			puts '</li>'
			log_garbage = true
		end
	end
}

puts '</ul>'
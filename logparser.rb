#!/usr/bin/env ruby
require 'fcntl'

log_garbage = true
fd = STDOUT.fcntl(Fcntl::F_DUPFD)
out = IO.new(fd, mode: 'w:UTF-8')

out.puts '<ul>'
File.open('changelog.xml', 'r:UTF-8') do |fp|
	while line = fp.gets
		next if line.strip.length == 0
		if line.match(/^\s+.*$/)
			line.gsub!('<', '&lt;')
			line.gsub!('>', '&gt;')
			if log_garbage
				out.puts '<li>' + line
				log_garbage = false
			else
				out.puts line
			end
		else
			if not log_garbage
				out.puts '</li>'
				log_garbage = true
			end
		end
	end
end

out.puts '</ul>'
import 'package:intl/intl.dart';

final DateFormat _short = DateFormat('MMM d, y');
final DateFormat _iso = DateFormat('yyyy-MM-dd');
final DateFormat _dayTime = DateFormat('MMM d, y · HH:mm');

String shortDate(DateTime date) => _short.format(date);

String isoDate(DateTime date) => _iso.format(date);

String dateTimeLabel(DateTime date) => _dayTime.format(date.toLocal());

DateTime dateOnly(DateTime date) => DateTime(date.year, date.month, date.day);

DateTime? parseDate(String? value) =>
    value == null || value.isEmpty ? null : DateTime.tryParse(value);

/// "Due in 12 days", "Due today", "Overdue by 3 days".
String dueLabel(DateTime dueDate, {DateTime? today}) {
  final now = dateOnly(today ?? DateTime.now());
  final days = dateOnly(dueDate).difference(now).inDays;
  if (days == 0) return 'Due today';
  if (days == 1) return 'Due tomorrow';
  if (days > 0) return 'Due in $days days';
  if (days == -1) return 'Overdue by 1 day';
  return 'Overdue by ${-days} days';
}

/// "just now", "5 min ago", "3 h ago", "2 days ago".
String timeAgo(DateTime moment, {DateTime? now}) {
  final diff = (now ?? DateTime.now()).difference(moment.toLocal());
  if (diff.inSeconds < 60) return 'just now';
  if (diff.inMinutes < 60) return '${diff.inMinutes} min ago';
  if (diff.inHours < 24) return '${diff.inHours} h ago';
  if (diff.inDays == 1) return 'yesterday';
  return '${diff.inDays} days ago';
}

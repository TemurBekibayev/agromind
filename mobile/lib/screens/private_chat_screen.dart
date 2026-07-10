import 'dart:async';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../providers/providers.dart';

class PrivateChatScreen extends ConsumerStatefulWidget {
  final Map<String, dynamic> partner;

  const PrivateChatScreen({super.key, required this.partner});

  @override
  ConsumerState<PrivateChatScreen> createState() => _PrivateChatScreenState();
}

class _PrivateChatScreenState extends ConsumerState<PrivateChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();
  
  bool _isRecording = false;
  int _recordSeconds = 0;
  Timer? _recordTimer;
  bool _isSending = false;

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
    _recordTimer?.cancel();
    super.dispose();
  }

  void _scrollToBottom() {
    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        _scrollController.position.maxScrollExtent,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  String _formatTime(String? createdAt) {
    if (createdAt == null) return '';
    try {
      final dt = DateTime.parse(createdAt).toLocal();
      return DateFormat('HH:mm').format(dt);
    } catch (e) {
      return '';
    }
  }

  void _startMockRecording() {
    setState(() {
      _isRecording = true;
      _recordSeconds = 0;
    });
    _recordTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() {
        _recordSeconds++;
      });
    });
  }

  Future<void> _stopAndSendMockRecording() async {
    _recordTimer?.cancel();
    if (_recordSeconds < 1) {
      setState(() {
        _isRecording = false;
      });
      return;
    }

    setState(() {
      _isRecording = false;
      _isSending = true;
    });

    try {
      // Create a dummy audio file using systemTemp directory
      final tempDir = Directory.systemTemp;
      final file = File('${tempDir.path}/voice_message_${DateTime.now().millisecondsSinceEpoch}.mp3');
      await file.writeAsBytes(List.generate(100, (index) => index % 256));

      final success = await ref
          .read(privateMessagesProvider(widget.partner['id']).notifier)
          .sendMessage(audioPath: file.path);

      if (success) {
        Future.delayed(const Duration(milliseconds: 100), _scrollToBottom);
      } else {
        _showErrorSnackBar('Ovozi xabarni yuborishda xatolik yuz berdi.');
      }
    } catch (e) {
      _showErrorSnackBar('Fayl tizimida xatolik: $e');
    } finally {
      setState(() {
        _isSending = false;
      });
    }
  }

  void _showErrorSnackBar(String msg) {
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(msg), backgroundColor: Colors.red),
      );
    }
  }

  Future<void> _sendTextMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    _messageController.clear();
    setState(() {
      _isSending = true;
    });

    final success = await ref
        .read(privateMessagesProvider(widget.partner['id']).notifier)
        .sendMessage(message: text);

    setState(() {
      _isSending = false;
    });

    if (success) {
      Future.delayed(const Duration(milliseconds: 100), _scrollToBottom);
    } else {
      _showErrorSnackBar('Xabarni yuborishda xatolik yuz berdi.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final partnerId = widget.partner['id'] as int;
    final messagesState = ref.watch(privateMessagesProvider(partnerId));
    final authState = ref.watch(authProvider);
    final currentUserId = authState.user?['id'];

    // Auto-scroll on new message list loaded
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
      }
    });

    return Scaffold(
      backgroundColor: Colors.grey[100],
      appBar: AppBar(
        backgroundColor: const Color(0xFF1A3C2A),
        foregroundColor: Colors.white,
        elevation: 1,
        title: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              widget.partner['name'] ?? 'Suhbatdosh',
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
            ),
            Text(
              widget.partner['role'] == 'farmer' ? 'Fermer (Amudaryo)' : 'Nazoratchi (Amudaryo)',
              style: const TextStyle(fontSize: 11, color: Colors.white70),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref
                .read(privateMessagesProvider(partnerId).notifier)
                .fetchMessages(showLoading: true),
          ),
        ],
      ),
      body: Column(
        children: [
          Expanded(
            child: messagesState.when(
              data: (messages) {
                if (messages.isEmpty) {
                  return Center(
                    child: Padding(
                      padding: const EdgeInsets.all(24.0),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(Icons.chat_bubble_outline_rounded, size: 64, color: Colors.grey[400]),
                          const SizedBox(height: 16),
                          const Text(
                            'Shaxsiy yozishmalar mavjud emas',
                            style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold, color: Colors.black54),
                          ),
                          const SizedBox(height: 6),
                          const Text(
                            'Birinchi bo\'lib salom yo\'llang!',
                            textAlign: TextAlign.center,
                            style: TextStyle(fontSize: 12, color: Colors.black38),
                          ),
                        ],
                      ),
                    ),
                  );
                }

                return ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.all(16.0),
                  itemCount: messages.length,
                  itemBuilder: (context, index) {
                    final msg = messages[index];
                    final isMe = msg['sender_id'] == currentUserId;
                    final isRead = msg['is_read'] == true;
                    final hasAudio = msg['audio_path'] != null;

                    return Align(
                      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 10.0),
                        constraints: BoxConstraints(
                          maxWidth: MediaQuery.of(context).size.width * 0.75,
                        ),
                        padding: const EdgeInsets.all(12.0),
                        decoration: BoxDecoration(
                          color: isMe ? const Color(0xFF1A3C2A) : Colors.white,
                          borderRadius: BorderRadius.only(
                            topLeft: const Radius.circular(16),
                            topRight: const Radius.circular(16),
                            bottomLeft: isMe ? const Radius.circular(16) : Radius.zero,
                            bottomRight: isMe ? Radius.zero : const Radius.circular(16),
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.04),
                              blurRadius: 4,
                              offset: const Offset(0, 2),
                            )
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (hasAudio)
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  IconButton(
                                    icon: Icon(
                                      Icons.play_circle_fill_rounded,
                                      color: isMe ? Colors.white : const Color(0xFF1A3C2A),
                                      size: 32,
                                    ),
                                    onPressed: () {
                                      ScaffoldMessenger.of(context).showSnackBar(
                                        const SnackBar(
                                          content: Text('🎙️ Ovozli xabar ijro etilmoqda...'),
                                          duration: Duration(seconds: 2),
                                        ),
                                      );
                                    },
                                  ),
                                  const SizedBox(width: 8),
                                  Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Container(
                                        width: 100,
                                        height: 3,
                                        color: isMe ? Colors.white38 : Colors.grey[300],
                                        child: Align(
                                          alignment: Alignment.centerLeft,
                                          child: Container(
                                            width: 40,
                                            height: 3,
                                            color: isMe ? Colors.white : const Color(0xFF1A3C2A),
                                          ),
                                        ),
                                      ),
                                      const SizedBox(height: 4),
                                      Text(
                                        'Ovozli xabar',
                                        style: TextStyle(
                                          fontSize: 11,
                                          color: isMe ? Colors.white70 : Colors.black54,
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              )
                            else
                              Text(
                                msg['message'] ?? '',
                                style: TextStyle(
                                  fontSize: 14,
                                  color: isMe ? Colors.white : Colors.black87,
                                ),
                              ),
                            const SizedBox(height: 4),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.end,
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  _formatTime(msg['created_at']),
                                  style: TextStyle(
                                    fontSize: 9,
                                    color: isMe ? Colors.white70 : Colors.black38,
                                  ),
                                ),
                                if (isMe) ...[
                                  const SizedBox(width: 4),
                                  Icon(
                                    isRead ? Icons.done_all_rounded : Icons.done_rounded,
                                    size: 13,
                                    color: isRead ? Colors.lightBlueAccent : Colors.white60,
                                  ),
                                ],
                              ],
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                );
              },
              error: (e, __) => Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text('Suhbatni yuklashda xatolik yuz berdi.', style: TextStyle(color: Colors.red)),
                    const SizedBox(height: 8),
                    ElevatedButton(
                      onPressed: () => ref
                          .read(privateMessagesProvider(partnerId).notifier)
                          .fetchMessages(showLoading: true),
                      child: const Text('Qayta urinish'),
                    ),
                  ],
                ),
              ),
              loading: () => const Center(
                child: CircularProgressIndicator(color: Color(0xFF1A3C2A)),
              ),
            ),
          ),
          
          // Audio yozib turilganda ko'rsatiladigan holat
          if (_isRecording)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 16.0, vertical: 12.0),
              color: Colors.red[50],
              child: Row(
                children: [
                  const Icon(Icons.mic_rounded, color: Colors.red, size: 24),
                  const SizedBox(width: 8),
                  Text(
                    'Ovoz yozilmoqda: ${_recordSeconds ~/ 60}:${(_recordSeconds % 60).toString().padLeft(2, '0')}',
                    style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold),
                  ),
                  const Spacer(),
                  TextButton(
                    onPressed: () {
                      setState(() {
                        _recordTimer?.cancel();
                        _isRecording = false;
                      });
                    },
                    child: const Text('Bekor qilish', style: TextStyle(color: Colors.grey)),
                  ),
                  const SizedBox(width: 8),
                  ElevatedButton(
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white),
                    onPressed: _stopAndSendMockRecording,
                    child: const Text('Yuborish'),
                  ),
                ],
              ),
            ),

          if (!_isRecording)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 8.0),
              decoration: BoxDecoration(
                color: Colors.white,
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.05),
                    blurRadius: 4,
                    offset: const Offset(0, -2),
                  )
                ],
              ),
              child: SafeArea(
                child: Row(
                  children: [
                    // Mic button for voice messages
                    IconButton(
                      icon: const Icon(Icons.mic_none_rounded, color: Color(0xFF1A3C2A)),
                      onPressed: _isSending ? null : _startMockRecording,
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Container(
                        decoration: BoxDecoration(
                          color: Colors.grey[100],
                          borderRadius: BorderRadius.circular(24),
                        ),
                        child: TextField(
                          controller: _messageController,
                          textCapitalization: TextCapitalization.sentences,
                          enabled: !_isSending,
                          decoration: const InputDecoration(
                            hintText: 'Xabar yozing...',
                            hintStyle: TextStyle(fontSize: 14, color: Colors.grey),
                            contentPadding: EdgeInsets.symmetric(horizontal: 16.0, vertical: 10.0),
                            border: InputBorder.none,
                          ),
                          maxLines: 4,
                          minLines: 1,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    _isSending
                        ? const SizedBox(
                            width: 20,
                            height: 20,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF1A3C2A)),
                          )
                        : CircleAvatar(
                            backgroundColor: const Color(0xFF1A3C2A),
                            radius: 20,
                            child: IconButton(
                              icon: const Icon(Icons.send_rounded, color: Colors.white, size: 18),
                              onPressed: _sendTextMessage,
                            ),
                          ),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }
}

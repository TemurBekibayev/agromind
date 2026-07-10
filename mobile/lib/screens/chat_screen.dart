import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../providers/providers.dart';
import 'private_chat_screen.dart';

class ChatScreen extends ConsumerStatefulWidget {
  const ChatScreen({super.key});

  @override
  ConsumerState<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends ConsumerState<ChatScreen> {
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _scrollController = ScrollController();

  @override
  void dispose() {
    _messageController.dispose();
    _scrollController.dispose();
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

  Future<void> _sendMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    _messageController.clear();
    final success = await ref.read(chatMessagesProvider.notifier).sendMessage(text);
    
    if (success) {
      Future.delayed(const Duration(milliseconds: 100), _scrollToBottom);
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Xabar yuborishda xatolik yuz berdi.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _showSupportDialog(BuildContext context) {
    final controller = TextEditingController();
    showDialog(
      context: context,
      builder: (context) {
        return AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text(
            'Adminga Murojaat Yo\'llash',
            style: TextStyle(color: Color(0xFF1A3C2A), fontWeight: FontWeight.bold),
          ),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'Murojaatingiz to\'g\'ridan-to\'g\'ri tizim administratoriga yuboriladi.',
                style: TextStyle(fontSize: 12, color: Colors.black54),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: controller,
                maxLines: 4,
                decoration: InputDecoration(
                  hintText: 'Murojaat matnini yozing...',
                  hintStyle: const TextStyle(fontSize: 13),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: const BorderSide(color: Color(0xFF1A3C2A), width: 1.5),
                  ),
                ),
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(context),
              child: const Text('Bekor qilish', style: TextStyle(color: Colors.grey)),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF1A3C2A),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              onPressed: () async {
                final txt = controller.text.trim();
                if (txt.isEmpty) return;
                
                Navigator.pop(context);
                
                try {
                  final api = ref.read(apiServiceProvider);
                  final res = await api.sendSupportMessage(txt);
                  if (res.data['status'] == 'success') {
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(
                          content: Text('Murojaatingiz adminga muvaffaqiyatli yuborildi.'),
                          backgroundColor: Color(0xFF2E7D32),
                        ),
                      );
                    }
                  }
                } catch (e) {
                  if (context.mounted) {
                    ScaffoldMessenger.of(context).showSnackBar(
                      SnackBar(
                        content: Text('Xatolik yuz berdi: $e'),
                        backgroundColor: Colors.red,
                      ),
                    );
                  }
                }
              },
              child: const Text('Yuborish'),
            ),
          ],
        );
      },
    );
  }

  Widget _buildGroupChatBody(AsyncValue<List<dynamic>> chatState, int? currentUserId) {
    // Scroll to bottom on load
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_scrollController.hasClients) {
        _scrollController.jumpTo(_scrollController.position.maxScrollExtent);
      }
    });

    return Column(
      children: [
        Expanded(
          child: chatState.when(
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
                          'Hozircha suhbatlar yo\'q',
                          style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black54),
                        ),
                        const SizedBox(height: 8),
                        const Text(
                          'Birinchi bo\'lib xabar yozing va fermerlar bilan muloqotni boshlang!',
                          textAlign: TextAlign.center,
                          style: TextStyle(fontSize: 13, color: Colors.black38),
                        ),
                      ],
                    ),
                  ),
                );
              }

              return RefreshIndicator(
                onRefresh: () => ref.read(chatMessagesProvider.notifier).fetchMessages(),
                child: ListView.builder(
                  controller: _scrollController,
                  padding: const EdgeInsets.all(16.0),
                  physics: const AlwaysScrollableScrollPhysics(),
                  itemCount: messages.length,
                  itemBuilder: (context, index) {
                    final msg = messages[index];
                    final user = msg['user'] ?? {};
                    final senderId = user['id'];
                    final isMe = senderId == currentUserId;
                    final senderName = user['name'] ?? 'Fermer';
                    final regionName = user['region']?['name'] ?? '';
                    final district = user['district'] ?? '';
                    
                    String location = '';
                    if (regionName.isNotEmpty) {
                      location = regionName;
                      if (district.isNotEmpty) {
                        location += ', $district';
                      }
                    }

                    return Align(
                      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                      child: Container(
                        margin: const EdgeInsets.only(bottom: 12.0),
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
                              color: Colors.black.withOpacity(0.05),
                              blurRadius: 4,
                              offset: const Offset(0, 2),
                            )
                          ],
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            if (!isMe) ...[
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Text(
                                    senderName,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.bold,
                                      color: Color(0xFF2E6F40),
                                    ),
                                  ),
                                  if (location.isNotEmpty) ...[
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        '($location)',
                                        style: TextStyle(
                                          fontSize: 10,
                                          color: Colors.grey[500],
                                          overflow: TextOverflow.ellipsis,
                                        ),
                                        maxLines: 1,
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                              const SizedBox(height: 4),
                            ],
                            Text(
                              msg['message'] ?? '',
                              style: TextStyle(
                                fontSize: 14,
                                color: isMe ? Colors.white : Colors.black87,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Align(
                              alignment: Alignment.bottomRight,
                              child: Text(
                                _formatTime(msg['created_at']),
                                style: TextStyle(
                                  fontSize: 9,
                                  color: isMe ? Colors.white70 : Colors.black38,
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              );
            },
            error: (e, __) => Center(
              child: Padding(
                padding: const EdgeInsets.all(16.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Text(
                      'Xabarlarni yuklashda xatolik yuz berdi.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.red),
                    ),
                    const SizedBox(height: 8),
                    ElevatedButton(
                      onPressed: () => ref.read(chatMessagesProvider.notifier).fetchMessages(),
                      child: const Text('Qayta urinish'),
                    ),
                  ],
                ),
              ),
            ),
            loading: () => const Center(
              child: CircularProgressIndicator(
                color: Color(0xFF1A3C2A),
              ),
            ),
          ),
        ),
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
                Expanded(
                  child: Container(
                    decoration: BoxDecoration(
                      color: Colors.grey[100],
                      borderRadius: BorderRadius.circular(24),
                    ),
                    child: TextField(
                      controller: _messageController,
                      textCapitalization: TextCapitalization.sentences,
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
                CircleAvatar(
                  backgroundColor: const Color(0xFF1A3C2A),
                  radius: 20,
                  child: IconButton(
                    icon: const Icon(Icons.send_rounded, color: Colors.white, size: 18),
                    onPressed: _sendMessage,
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildPrivateChatsBody() {
    final usersState = ref.watch(privateChatUsersProvider);

    return usersState.when(
      data: (chats) {
        if (chats.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24.0),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.people_outline_rounded, size: 64, color: Colors.grey[400]),
                  const SizedBox(height: 16),
                  const Text(
                    'Shaxsiy suhbatlar yo\'q',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black54),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Sizning tumaningizdagi boshqa fermerlar ro\'yxatga olinsa, bu yerda ko\'rinadi.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 13, color: Colors.black38),
                  ),
                ],
              ),
            ),
          );
        }

        return RefreshIndicator(
          onRefresh: () => ref.read(privateChatUsersProvider.notifier).fetchChatUsers(),
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(vertical: 8),
            itemCount: chats.length,
            separatorBuilder: (context, index) => const Divider(height: 1, indent: 72),
            itemBuilder: (context, index) {
              final chat = chats[index];
              final partner = chat['partner'] ?? {};
              final lastMsg = chat['last_message'];
              final unreadCount = chat['unread_count'] ?? 0;

              String subtitleText = '';
              if (lastMsg != null) {
                if (lastMsg['message'] != null && lastMsg['message'].isNotEmpty) {
                  subtitleText = lastMsg['message'];
                } else if (lastMsg['audio_path'] != null) {
                  subtitleText = '🎙️ Ovozli xabar';
                }
              } else {
                subtitleText = partner['role'] == 'farmer' ? 'Dehqon' : 'Nazoratchi';
              }

              return ListTile(
                leading: CircleAvatar(
                  backgroundColor: const Color(0xFF1A3C2A).withOpacity(0.1),
                  child: Text(
                    (partner['name'] ?? 'F').substring(0, 1).toUpperCase(),
                    style: const TextStyle(
                      color: Color(0xFF1A3C2A),
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                title: Row(
                  mainAxisAlignment: MainAxisAlignment.between,
                  children: [
                    Expanded(
                      child: Text(
                        partner['name'] ?? 'Noma\'lum',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (lastMsg != null)
                      Text(
                        _formatTime(lastMsg['created_at']),
                        style: const TextStyle(fontSize: 11, color: Colors.grey),
                      ),
                  ],
                ),
                subtitle: Padding(
                  padding: const EdgeInsets.only(top: 4.0),
                  child: Text(
                    subtitleText,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: unreadCount > 0 ? Colors.black87 : Colors.grey[650],
                      fontSize: 13,
                      fontWeight: unreadCount > 0 ? FontWeight.w600 : FontWeight.normal,
                    ),
                  ),
                ),
                trailing: unreadCount > 0
                    ? Container(
                        padding: const EdgeInsets.all(6),
                        decoration: const BoxDecoration(
                          color: Color(0xFF2E7D32),
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          '$unreadCount',
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 10,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      )
                    : null,
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (context) => PrivateChatScreen(partner: partner),
                    ),
                  ).then((_) {
                    ref.read(privateChatUsersProvider.notifier).fetchChatUsers();
                  });
                },
              );
            },
          ),
        );
      },
      error: (e, __) => Center(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Text(
                'Suhbatdoshlarni yuklashda xatolik yuz berdi.',
                textAlign: TextAlign.center,
                style: TextStyle(color: Colors.red),
              ),
              const SizedBox(height: 8),
              ElevatedButton(
                onPressed: () => ref.read(privateChatUsersProvider.notifier).fetchChatUsers(),
                child: const Text('Qayta urinish'),
              ),
            ],
          ),
        ),
      ),
      loading: () => const Center(
        child: CircularProgressIndicator(
          color: Color(0xFF1A3C2A),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final chatState = ref.watch(chatMessagesProvider);
    final authState = ref.watch(authProvider);
    final currentUserId = authState.user?['id'];

    return DefaultTabController(
      length: 2,
      child: Scaffold(
        backgroundColor: Colors.grey[100],
        appBar: AppBar(
          backgroundColor: const Color(0xFF1A3C2A),
          foregroundColor: Colors.white,
          elevation: 1,
          title: const Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Muloqotlar Guruhi',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              Text(
                'Guruh va Shaxsiy yozishmalar',
                style: TextStyle(fontSize: 11, color: Colors.white70),
              ),
            ],
          ),
          actions: [
            IconButton(
              icon: const Icon(Icons.support_agent_rounded, tooltip: 'Adminga murojaat'),
              onPressed: () => _showSupportDialog(context),
            ),
            IconButton(
              icon: const Icon(Icons.refresh_rounded),
              onPressed: () {
                ref.read(chatMessagesProvider.notifier).fetchMessages();
                ref.read(privateChatUsersProvider.notifier).fetchChatUsers();
              },
            ),
          ],
          bottom: const TabBar(
            indicatorColor: Colors.white,
            labelColor: Colors.white,
            unselectedLabelColor: Colors.white60,
            tabs: [
              Tab(text: 'Umumiy Guruh'),
              Tab(text: 'Shaxsiy Chatlar'),
            ],
          ),
        ),
        body: TabBarView(
          children: [
            _buildGroupChatBody(chatState, currentUserId),
            _buildPrivateChatsBody(),
          ],
        ),
      ),
    );
  }
}

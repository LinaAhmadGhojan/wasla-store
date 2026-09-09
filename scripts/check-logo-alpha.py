from PIL import Image

im = Image.open(r'E:\erp\wasla-store\public\brand\wasla-id-horizontal.png')
print('mode', im.mode, 'size', im.size)
a = im.getchannel('A')
hist = a.histogram()
print('transparent', hist[0])
print('opaque', hist[255])
print('partial', sum(hist[1:255]))
print('corner0', im.getpixel((0, 0)))
print('corner10', im.getpixel((10, 10)))
print('center', im.getpixel((640, 360)))
